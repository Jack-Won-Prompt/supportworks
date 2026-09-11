import { mkdir } from 'node:fs/promises';
import { dirname } from 'node:path';
import { ApiClient, JobSpec } from './api.js';
import { config } from './config.js';
import {
    handoverPath,
    handoverPrompt,
    resumePrompt,
    validateHandover,
    writeFallbackHandover,
} from './handover.js';
import { log, JobLogWriter } from './logger.js';
import { PermissionGate } from './permissions.js';
import { loadProjectRules } from './project-context.js';
import { Sandbox } from './sandbox.js';
import type { SessionAdapter, TurnUsage } from './session/adapter.js';
import { SdkSessionAdapter } from './session/sdk-adapter.js';

const FIXED_HEADER = (jobId: number, root: string) =>
    [
        `You are executing work order #${jobId} from SupportWorks.`,
        `Working directory ${root} is enforced by the daemon; access outside it is blocked.`,
        'Never push to remote. When reading long outputs (test logs, large files) use head/tail/grep',
        'to keep them short. When you need a decision from the human, ask a clear question and stop.',
    ].join('\n');

/** 프롬프트 조각 구분자. 헤더 / 규칙 / 본문을 빈 줄로 나눈다. */
const SEPARATOR = String.fromCharCode(10, 10);

export interface SessionManagerHooks {
    onTerminal(reason: 'completed' | 'failed' | 'cancelled', detail?: string): void;
}

/** 세션 실행기 생성자. 테스트가 가짜 어댑터를 끼울 수 있게 밖에서 받는다. */
export type AdapterFactory = () => SessionAdapter;

/**
 * job 하나의 세션 생애를 관리한다.
 *
 * 책임: 세션 시작/교체, 턴 집계, 인수인계 트리거, 입력 큐(교체 중 잠금),
 * 로그·메시지 배치 전송. 동시성과 큐잉은 JobManager 가 맡는다.
 */
export class SessionManager {
    private adapter: SessionAdapter | null = null;

    private sandbox!: Sandbox;

    /** CLAUDE.md 등 저장소 규칙. 세션을 교체해도 매번 다시 넣는다. */
    private projectRules: string | null = null;

    private gate!: PermissionGate;

    private sessionId: string | null = null;

    /** session_id 가 도착하기를 기다리는 쪽. system/init 이 오면 한꺼번에 깨운다. */
    private sessionIdWaiters: ((id: string) => void)[] = [];

    private sessionIndex = 0;

    /** 인수인계 중에는 사용자 메시지를 여기 모았다가 교체 후 순서대로 주입한다. */
    private heldMessages: { id: number; content: string }[] = [];

    private holding = false;

    private logSeq = 0;

    private messageSeq = 0;

    private logBuffer: { seq: number; type: any; content: string; raw?: unknown }[] = [];

    private flushTimer: NodeJS.Timeout | null = null;

    private recentAssistant: string[] = [];

    private lastUsage: TurnUsage = { contextTokens: 0, cumulativeCostUsd: 0 };

    private startedAt = Date.now();

    private stopped = false;

    private handoverInFlight = false;

    private awaitingHandoverDoc: ((v: void) => void) | null = null;

    private readonly writer: JobLogWriter;

    constructor(
        private readonly api: ApiClient,
        private readonly job: JobSpec,
        private readonly hooks: SessionManagerHooks,
        private readonly createAdapter: AdapterFactory = () => new SdkSessionAdapter(),
    ) {
        this.writer = new JobLogWriter(job.job_id);
    }

    // ── 시작 ────────────────────────────────────────────────────────────────

    async run(root: string): Promise<void> {
        this.sandbox = await Sandbox.create(root);

        this.gate = new PermissionGate(
            this.api,
            this.job.job_id,
            this.sandbox,
            this.job.permission_mode,
            // 서버 config('aiw.auto_approvable') 와 같은 목록을 유지한다(이중 방어).
            // Bash 포함은 운영자 결정이다 — config/aiw.php 주석 참고.
            ['Read', 'Edit', 'Write', 'Bash', 'Glob', 'Grep'],
            {
                onWaiting: () => this.pushLog('daemon', '사용자 승인 대기 중'),
                onResumed: () => void this.api.quiet('status running', () =>
                    this.api.status(this.job.job_id, 'running'),
                ),
                onBlocked: (tool, reason) =>
                    this.pushLog('error', `샌드박스 차단: ${tool} — ${reason}`),
            },
        );

        this.projectRules = await loadProjectRules(root);

        // 최초 프롬프트에 지시문을 담고, 이후 세션은 인수인계 문서로 잇는다.
        await this.startSession(
            this.compose(this.job.instruction),
            this.job.resume_session_id ?? null,
        );

        // session_id 가 도착한 뒤에 보고한다. 이게 있어야 나중에 resume 이 성립한다.
        const sessionId = await this.waitForSessionId();

        if (!sessionId) {
            this.pushLog('error', '세션 ID를 받지 못했습니다. 이 작업은 나중에 이어서 실행할 수 없습니다.');
        }

        await this.api.quiet('start', () =>
            this.api.start(this.job.job_id, sessionId ?? 'unknown', Boolean(this.job.resume_session_id)),
        );
    }

    /**
     * 고정 헤더 + 프로젝트 규칙 + 본문. 세션을 교체해도 헤더와 규칙은 다시 붙는다 —
     * 새 세션은 이전 맥락을 물려받지 않기 때문이다.
     */
    private compose(body: string): string {
        return [FIXED_HEADER(this.job.job_id, this.sandbox.root), this.projectRules, body]
            .filter(Boolean)
            .join(SEPARATOR);
    }

    /**
     * SDK 가 session_id 를 알려줄 때까지 기다린다.
     *
     * adapter.start() 는 스트림을 걸어 놓고 즉시 반환하고, session_id 는 그 뒤
     * 첫 system/init 메시지에 실려 온다. 기다리지 않고 보고하면 'unknown' 이
     * 저장되어 이후 어떤 resume 도 성립하지 않는다 — 실제로 그렇게 기록돼
     * 재기동 복구가 "--resume unknown" 으로 실패했다.
     */
    private waitForSessionId(timeoutMs = 60_000): Promise<string | null> {
        if (this.sessionId) {
            return Promise.resolve(this.sessionId);
        }

        return new Promise((resolve) => {
            const wake = (id: string) => {
                clearTimeout(timer);
                resolve(id);
            };

            const timer = setTimeout(() => {
                this.sessionIdWaiters = this.sessionIdWaiters.filter((w) => w !== wake);
                resolve(null);
            }, timeoutMs);

            this.sessionIdWaiters.push(wake);
        });
    }

    private async startSession(prompt: string, resumeSessionId: string | null): Promise<void> {
        const adapter = this.createAdapter();

        this.adapter = adapter;
        this.startedAt = Date.now();

        await adapter.start(
            {
                prompt,
                cwd: this.sandbox.root,
                model: this.job.model,
                allowedTools: this.job.allowed_tools,
                resumeSessionId,
                canUseTool: (tool, input) => this.gate.check(tool, input),
            },
            {
                onSessionId: (id) => {
                    this.sessionId = id;

                    for (const wake of this.sessionIdWaiters.splice(0)) {
                        wake(id);
                    }
                },
                onAssistantText: (text) => this.onAssistantText(text),
                onLog: (type, content, raw) => this.pushLog(type, content, raw),
                onTurnEnd: (usage, completed) => void this.onTurnEnd(usage, completed),
                onFinished: (error) => void this.onFinished(error),
            },
        );
    }

    // ── 턴 처리 ─────────────────────────────────────────────────────────────

    private onAssistantText(text: string): void {
        this.recentAssistant.push(text);

        // 인수인계 문서 작성 완료 신호. 본문은 파일에서 읽는다.
        if (this.handoverInFlight && text.includes('HANDOVER_DONE')) {
            this.awaitingHandoverDoc?.();
            this.awaitingHandoverDoc = null;

            return;
        }

        void this.api.quiet('messages', () =>
            this.api.messages(this.job.job_id, [
                { seq: this.messageSeq++, role: 'assistant', content: text },
            ]),
        );
    }

    private async onTurnEnd(usage: TurnUsage, completed = false): Promise<void> {
        this.lastUsage = usage;

        const flags = await this.api.quiet('status', () =>
            this.api.status(this.job.job_id, 'running', {
                context_tokens: usage.contextTokens,
                cost_usd: usage.cumulativeCostUsd,
            }),
        );

        // 서버가 비용 상한·취소를 알렸다면 즉시 멈춘다.
        if (flags?.cost_over_limit || flags?.cancel_requested) {
            this.pushLog('daemon', '서버가 중단을 지시했습니다.');
            await this.stop('cancelled', '서버 지시로 중단');

            return;
        }

        if (this.handoverInFlight) {
            return;
        }

        // batch 에서 작업이 끝났으면 완료가 우선이다. 끝난 일을 정리해 넘길 이유가 없다.
        if (completed && this.job.mode === 'batch') {
            await this.stop('completed');

            return;
        }

        // 인수인계는 항상 턴 경계에서만. 툴 실행 중에 끊지 않는다.
        const ratio = this.job.context_limit_tokens > 0
            ? usage.contextTokens / this.job.context_limit_tokens
            : 0;

        if (ratio >= config.contextHandoverRatio) {
            await this.performHandover('context');

            return;
        }

        if (this.job.mode === 'interactive') {
            await this.api.quiet('waiting_input', () =>
                this.api.status(this.job.job_id, 'waiting_input'),
            );
        }
    }

    private async onFinished(error?: Error): Promise<void> {
        if (this.stopped || this.handoverInFlight) {
            return;
        }

        if (error) {
            await this.stop('failed', error.message);

            return;
        }

        // batch 는 턴이 끝나면 완료. interactive 는 사용자가 끝낼 때까지 유지한다.
        if (this.job.mode === 'batch') {
            await this.stop('completed');
        }
    }

    // ── 외부 입력 ───────────────────────────────────────────────────────────

    /** 웹에서 온 사용자 메시지. 인수인계 중이면 보류했다가 교체 후 주입한다. */
    async deliver(messageId: number, content: string): Promise<void> {
        if (this.holding) {
            this.heldMessages.push({ id: messageId, content });
            this.pushLog('daemon', '컨텍스트 정리 중 — 메시지를 보류합니다.');

            return;
        }

        this.adapter?.send(content);
        await this.api.quiet('delivered', () => this.api.markDelivered(this.job.job_id, messageId));
        await this.api.quiet('running', () => this.api.status(this.job.job_id, 'running'));
    }

    resolvePermission(requestKey: string, allowed: boolean, reason?: string): void {
        this.gate.resolve(requestKey, { allowed, reason });
    }

    /** 사용자가 "컨텍스트 정리"를 눌렀다. */
    async requestHandover(): Promise<void> {
        if (!this.handoverInFlight) {
            await this.performHandover('manual');
        }
    }

    /** 사용자가 "세션 종료"를 눌렀다. 마지막 턴을 기다렸다가 결과를 보고한다. */
    async requestEnd(): Promise<void> {
        await this.stop('completed');
    }

    async requestCancel(reason = '사용자가 취소했습니다.'): Promise<void> {
        this.adapter?.interrupt();
        await this.stop('cancelled', reason);
    }

    // ── 인수인계 ────────────────────────────────────────────────────────────

    private async performHandover(reason: 'context' | 'manual'): Promise<void> {
        this.handoverInFlight = true;
        this.holding = true;

        const endedSessionId = this.sessionId ?? 'unknown';
        const nextIndex = this.sessionIndex + 1;
        const docPath = handoverPath(this.sandbox.root, this.job.job_id, nextIndex);

        this.pushLog('handover', `컨텍스트 정리 시작 (${reason})`);
        await this.api.quiet('handover status', () => this.api.status(this.job.job_id, 'handover'));

        await mkdir(dirname(docPath), { recursive: true });

        // 현재 세션에 문서 작성을 시킨다. 최대 2회 시도.
        let validation = { ok: false, content: '', missing: [] as string[] };

        for (let attempt = 0; attempt < 2 && !validation.ok; attempt++) {
            await this.askForHandoverDoc(nextIndex);
            validation = await validateHandover(docPath);

            if (!validation.ok && attempt === 0) {
                this.pushLog('handover', `인수인계 문서 형식 미달(누락: ${validation.missing.join(', ')}) — 재요청`);
            }
        }

        if (!validation.ok) {
            this.pushLog('handover', '인수인계 문서 생성 실패 — 데몬이 최소 요약을 작성합니다.');
            validation = {
                ok: true,
                missing: [],
                content: await writeFallbackHandover(
                    docPath,
                    this.job.job_id,
                    this.job.instruction,
                    this.recentAssistant,
                ),
            };
        }

        await this.adapter?.close();

        // 새 세션. resume 을 쓰지 않고 문서로만 맥락을 잇는다.
        this.sessionIndex = nextIndex;
        this.recentAssistant = [];
        this.handoverInFlight = false;

        this.sessionId = null;
        await this.startSession(
            this.compose(resumePrompt(this.job.instruction, validation.content)),
            null,
        );

        const newSessionId = await this.waitForSessionId();

        await this.api.quiet('handover', () =>
            this.api.handover(this.job.job_id, {
                ended_session_id: endedSessionId,
                new_session_id: newSessionId ?? 'unknown',
                document_path: docPath,
                summary: validation.content,
                seq: this.messageSeq++,
                reason,
            }),
        );

        // 보류했던 메시지를 순서대로 주입한다.
        this.holding = false;
        const held = this.heldMessages.splice(0);

        for (const message of held) {
            await this.deliver(message.id, message.content);
        }
    }

    private askForHandoverDoc(index: number): Promise<void> {
        return new Promise<void>((resolve) => {
            // 응답이 오지 않아도 영원히 매달리지 않는다.
            const timer = setTimeout(() => {
                this.awaitingHandoverDoc = null;
                resolve();
            }, 120_000);

            this.awaitingHandoverDoc = () => {
                clearTimeout(timer);
                resolve();
            };

            this.adapter?.send(handoverPrompt(this.job.job_id, index));
        });
    }

    // ── 종료 ────────────────────────────────────────────────────────────────

    async stop(reason: 'completed' | 'failed' | 'cancelled', detail?: string): Promise<void> {
        if (this.stopped) {
            return;
        }

        this.stopped = true;
        this.gate?.abortAll('작업이 종료되었습니다.');
        await this.flushLogs();
        await this.adapter?.close();

        this.hooks.onTerminal(reason, detail);
    }

    get metrics() {
        return {
            durationMs: Date.now() - this.startedAt,
            costUsd: this.lastUsage.cumulativeCostUsd,
            contextTokens: this.lastUsage.contextTokens,
        };
    }

    // ── 로그 배치 ───────────────────────────────────────────────────────────

    pushLog(type: 'system' | 'tool_use' | 'tool_result' | 'result' | 'error' | 'daemon' | 'handover', content: string, raw?: unknown): void {
        void this.writer.write({ type, content, raw });

        this.logBuffer.push({ seq: this.logSeq++, type, content, raw });

        if (this.logBuffer.length >= 20) {
            void this.flushLogs();

            return;
        }

        // 1초 또는 20건. 화면이 뚝뚝 끊기지 않으면서 요청도 과하지 않게.
        this.flushTimer ??= setTimeout(() => void this.flushLogs(), 1000);
    }

    private async flushLogs(): Promise<void> {
        if (this.flushTimer) {
            clearTimeout(this.flushTimer);
            this.flushTimer = null;
        }

        const batch = this.logBuffer.splice(0);

        if (!batch.length) {
            return;
        }

        const flags = await this.api.quiet('logs', () => this.api.logs(this.job.job_id, batch));

        if (flags?.cancel_requested && !this.stopped) {
            log('info', '로그 응답에서 취소 신호를 받았습니다.', { jobId: this.job.job_id });
            await this.stop('cancelled', '서버 지시로 중단');
        }
    }
}
