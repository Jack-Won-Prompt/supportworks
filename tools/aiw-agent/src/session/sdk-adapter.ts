import { query } from '@anthropic-ai/claude-agent-sdk';
import { config } from '../config.js';
import { log } from '../logger.js';
import type { SessionAdapter, SessionEvents, SessionStartOptions, TurnUsage } from './adapter.js';

/** 이 job 이 못 쓰는 툴은 컨텍스트에서 아예 제거한다. */
const ALL_TOOLS = ['Read', 'Edit', 'Write', 'Bash', 'Glob', 'Grep', 'WebFetch', 'WebSearch'];

interface Usage {
    input_tokens?: number;
    output_tokens?: number;
    cache_read_input_tokens?: number;
    cache_creation_input_tokens?: number;
}

/**
 * Claude Agent SDK 어댑터.
 *
 * 두 가지가 설계상 중요하다.
 *
 * 1. SDK 의 `allowedTools` 를 쓰지 않는다(빈 배열로 둔다).
 *    공식 문서: canUseTool 은 "permission flow 가 prompt 로 떨어질 때만" 호출되고
 *    allowedTools 로 자동 승인된 툴에는 호출되지 않는다. 거기에 job 의 allowed_tools 를
 *    넣으면 그 툴들이 canUseTool 을 건너뛰어 샌드박스 경로 검사가 통째로 무력화된다.
 *    쓸 수 있는 툴의 제한은 `disallowedTools`(컨텍스트에서 제거)로 하고,
 *    실행 허용 여부는 전부 canUseTool 한 지점에서 판단한다.
 *
 * 2. `permissionMode` 는 항상 'default' 다. job 의 acceptEdits 는 데몬이
 *    canUseTool 안에서 흉내 낸다 — 같은 이유로, 모든 호출이 예외 없이 한 지점을
 *    통과해야 한다.
 */
export class SdkSessionAdapter implements SessionAdapter {
    readonly kind = 'sdk' as const;

    readonly supportsPermissions = true;

    private controller: AbortController | null = null;

    private inputQueue: string[] = [];

    private notifyInput: (() => void) | null = null;

    private closed = false;

    /** 마지막으로 본 usage. 턴 종료 시 이 값이 곧 현재 컨텍스트 크기다. */
    private lastUsage: Usage = {};

    private cumulativeCost = 0;

    private pending: Promise<void> | null = null;

    async start(options: SessionStartOptions, events: SessionEvents): Promise<void> {
        this.controller = new AbortController();
        this.closed = false;
        this.inputQueue = [options.prompt];

        const disallowed = ALL_TOOLS.filter((t) => !options.allowedTools.includes(t));

        const run = async () => {
            try {
                const stream = query({
                    prompt: this.promptStream(),
                    options: {
                        cwd: options.cwd,
                        model: options.model ?? undefined,
                        // 위 주석 참조 — 자동 승인 경로를 만들지 않는다.
                        allowedTools: [],
                        disallowedTools: disallowed,
                        permissionMode: 'default',
                        // 파일시스템 설정을 읽지 않는다(SDK 격리 모드).
                        // 생략하면 이 PC 의 ~/.claude/settings.json 에 쌓인 허용 규칙이
                        // 적용되어 canUseTool 이 호출되지 않고 샌드박스가 통째로 우회된다.
                        // 실측으로 확인: PowerShell 툴이 승인 없이 실행됐다.
                        settingSources: [],
                        abortController: this.controller!,
                        // env 는 병합이 아니라 교체다. process.env 를 반드시 펼쳐 준다.
                        env: {
                            ...process.env,
                            ...(config.shell ? { CLAUDE_CODE_GIT_BASH_PATH: config.shell } : {}),
                        },
                        // 설치된 SDK(0.3.x)의 시그니처: (toolName, input, options) => PermissionResult.
                        // 공개 문서에는 다른 형태가 실려 있으나 패키지 타입 정의가 기준이다.
                        // ── 툴 게이트 ───────────────────────────────────────────
                        // canUseTool 은 "권한 흐름이 프롬프트로 떨어질 때만" 호출된다.
                        // Claude Code 의 기본 정책이 안전하다고 판단한 툴(Read 등)이나
                        // 설정의 허용 규칙에 걸린 툴은 canUseTool 을 거치지 않는다 —
                        // 실측으로 permission_mode=default 에서도 Read 가 승인 없이
                        // 실행되고 PowerShell·Agent 툴이 그냥 도는 것을 확인했다.
                        //
                        // 그래서 실제 강제는 PreToolUse 훅에서 한다. 이 훅은 권한 판단과
                        // 무관하게 모든 툴 호출에서 발동한다. canUseTool 은 프롬프트가
                        // 뜨는 경우를 위해 같은 게이트를 한 번 더 물려 둔다.
                        hooks: {
                            PreToolUse: [
                                {
                                    hooks: [
                                        async (input: any) => {
                                            const decision = await this.gate(
                                                options,
                                                String(input?.tool_name ?? ''),
                                                (input?.tool_input ?? {}) as Record<string, unknown>,
                                            );

                                            if (decision.allowed) {
                                                return { continue: true };
                                            }

                                            return {
                                                continue: true,
                                                hookSpecificOutput: {
                                                    hookEventName: 'PreToolUse' as const,
                                                    permissionDecision: 'deny' as const,
                                                    permissionDecisionReason: decision.reason
                                                        ?? '거부되었습니다.',
                                                },
                                            };
                                        },
                                    ],
                                },
                            ],
                        },
                        canUseTool: async (toolName: string, input: Record<string, unknown>) => {
                            const decision = await this.gate(options, toolName, input);

                            return decision.allowed
                                ? { behavior: 'allow' as const, updatedInput: input }
                                : { behavior: 'deny' as const, message: decision.reason ?? '거부되었습니다.' };
                        },
                        ...(options.resumeSessionId ? { resume: options.resumeSessionId } : {}),
                    },
                });

                for await (const message of stream) {
                    this.handleMessage(message, events);
                }

                events.onFinished();
            } catch (error) {
                if ((error as Error)?.name === 'AbortError') {
                    events.onFinished();

                    return;
                }

                events.onFinished(error as Error);
            }
        };

        this.pending = run();
    }

    /**
     * 툴 허용 판단 한 지점.
     *
     * 허용 목록은 여기서 강제한다. disallowedTools 로는 우리가 이름을 아는 툴만
     * 열거할 수 있는데, Claude Code 는 환경에 따라 모르는 툴을 노출한다
     * (Windows 의 PowerShell, 서브에이전트용 Agent 등).
     */
    private async gate(
        options: SessionStartOptions,
        toolName: string,
        input: Record<string, unknown>,
    ): Promise<{ allowed: boolean; reason?: string }> {
        if (!options.allowedTools.includes(toolName)) {
            return {
                allowed: false,
                reason: `이 작업 지시에서 허용된 툴이 아닙니다: ${toolName}. `
                    + `허용 툴: ${options.allowedTools.join(', ')}`,
            };
        }

        return options.canUseTool(toolName, input);
    }

    private handleMessage(message: any, events: SessionEvents): void {
        switch (message?.type) {
            case 'system': {
                if (typeof message.session_id === 'string') {
                    events.onSessionId(message.session_id);
                }
                events.onLog('system', String(message.subtype ?? 'system'), message);
                break;
            }

            case 'assistant': {
                // 내용과 usage 는 래퍼가 아니라 message.message(BetaMessage) 안에 있다.
                // 최상위 message.content 를 보면 항상 비어 있어 대화가 통째로 유실된다.
                const inner = message.message ?? {};

                this.captureAssistantUsage(inner);

                const blocks: any[] = Array.isArray(inner.content) ? inner.content : [];

                for (const block of blocks) {
                    if (block?.type === 'text' && typeof block.text === 'string' && block.text.trim()) {
                        events.onAssistantText(block.text);
                    }

                    if (block?.type === 'tool_use') {
                        events.onLog('tool_use', `${block.name} 호출`, block);
                    }
                }
                break;
            }

            case 'tool_result': {
                events.onLog('tool_result', summarize(message.content), message);
                break;
            }

            case 'user': {
                // 스트리밍 입력 에코. 화면에는 이미 있으므로 로그만 남긴다.
                events.onLog('system', '사용자 메시지 주입됨', message);
                break;
            }

            case 'result': {
                // result 의 usage 는 세션 누적이라 컨텍스트 크기가 아니다. 비용만 가져온다.
                if (typeof message?.total_cost_usd === 'number') {
                    this.cumulativeCost = message.total_cost_usd;
                }

                events.onLog('result', String(message.subtype ?? 'result'), message);
                // batch 에서 result 는 "작업이 끝났다"는 뜻이다. 완료 여부를 함께 알린다.
                events.onTurnEnd(this.usage(), message?.subtype === 'success');
                break;
            }

            default:
                break;
        }
    }

    /**
     * 컨텍스트 크기는 assistant 메시지의 usage 로만 잰다.
     *
     * result 메시지의 usage 는 세션 전체 누적이라(특히 cache_read 가 턴마다 더해진다)
     * 그걸 쓰면 작은 작업에서도 한도의 절반을 넘겨 인수인계가 헛돈다.
     */
    private captureAssistantUsage(inner: any): void {
        if (inner?.usage) {
            this.lastUsage = inner.usage as Usage;
        }
    }

    private usage(): TurnUsage {
        const u = this.lastUsage;

        return {
            contextTokens:
                (u.input_tokens ?? 0) +
                (u.cache_read_input_tokens ?? 0) +
                (u.cache_creation_input_tokens ?? 0),
            cumulativeCostUsd: this.cumulativeCost,
        };
    }

    /** 최초 프롬프트를 넣고, 이후 send() 가 넣는 메시지를 흘려 보낸다. */
    private async *promptStream(): AsyncGenerator<any> {
        while (!this.closed) {
            const next = this.inputQueue.shift();

            if (next !== undefined) {
                // SDKUserMessage 는 { type, message: MessageParam, parent_tool_use_id } 형태다.
                // 공개 문서의 { type, content } 형태로 보내면 Claude Code 가 프롬프트를
                // 받지 못해 아무 메시지도 내보내지 않은 채 대기한다.
                yield {
                    type: 'user' as const,
                    message: { role: 'user' as const, content: next },
                    parent_tool_use_id: null,
                };
                continue;
            }

            await new Promise<void>((resolve) => {
                this.notifyInput = resolve;
            });
        }
    }

    send(text: string): void {
        this.inputQueue.push(text);
        this.notifyInput?.();
        this.notifyInput = null;
    }

    interrupt(): void {
        this.controller?.abort();
    }

    async close(): Promise<void> {
        this.closed = true;
        this.notifyInput?.();
        this.notifyInput = null;
        this.controller?.abort();

        try {
            await this.pending;
        } catch (error) {
            log('warn', '세션 종료 중 오류', { error: String(error) });
        }
    }
}

function summarize(content: unknown): string {
    if (typeof content === 'string') {
        return content.slice(0, 200);
    }

    if (Array.isArray(content)) {
        const text = content
            .map((b: any) => (typeof b?.text === 'string' ? b.text : ''))
            .join(' ')
            .trim();

        return text ? text.slice(0, 200) : '툴 결과';
    }

    return '툴 결과';
}
