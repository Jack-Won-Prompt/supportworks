import { randomUUID } from 'node:crypto';
import { ApiClient } from './api.js';
import { config } from './config.js';
import { log } from './logger.js';
import { Sandbox } from './sandbox.js';

export interface PermissionDecision {
    allowed: boolean;
    reason?: string;
}

/**
 * 툴 실행 승인 게이트.
 *
 * 순서가 중요하다:
 *   1) 샌드박스 — 걸리면 서버에 묻지도 않고 즉시 거부한다. 사람이 실수로 [허용]을
 *      눌러도 통과하지 못해야 하는 것들이다.
 *   2) acceptEdits 흉내 — SDK 에 permissionMode 를 넘기지 않고 여기서 판단한다.
 *      Bash 는 절대 포함되지 않는다.
 *   3) 서버 승인 요청 — 사람의 결정을 기다린다.
 */
export class PermissionGate {
    /** request_key → 결정을 기다리는 resolver. Reverb 이벤트가 이걸 깨운다. */
    private readonly waiting = new Map<string, (d: PermissionDecision) => void>();

    constructor(
        private readonly api: ApiClient,
        private readonly jobId: number,
        private readonly sandbox: Sandbox,
        private readonly permissionMode: 'acceptEdits' | 'default',
        private readonly autoApprovable: string[],
        private readonly hooks: {
            onWaiting: () => void;
            onResumed: () => void;
            onBlocked: (toolName: string, reason: string) => void;
        },
    ) {}

    /** Reverb 의 permission.decided 를 받아 대기 중인 Promise 를 깨운다. */
    resolve(requestKey: string, decision: PermissionDecision): void {
        const resolver = this.waiting.get(requestKey);

        if (resolver) {
            this.waiting.delete(requestKey);
            resolver(decision);
        }
    }

    /** 세션 종료 시 대기 중인 것을 모두 거부로 풀어 준다(프로세스가 매달리지 않게). */
    abortAll(reason: string): void {
        for (const [key, resolver] of this.waiting) {
            this.waiting.delete(key);
            resolver({ allowed: false, reason });
        }
    }

    async check(toolName: string, input: unknown): Promise<PermissionDecision> {
        // 1) 샌드박스 — 무조건 거부. 서버에 묻지 않는다.
        const verdict = await this.sandbox.check(toolName, input);

        if (!verdict.allowed) {
            this.hooks.onBlocked(toolName, verdict.reason ?? '차단됨');

            return { allowed: false, reason: verdict.reason };
        }

        // 2) acceptEdits 흉내. Bash 는 여기 목록에 없다 —
        //    임의 명령 실행까지 자동 승인되면 승인 카드라는 방어선이 사라진다.
        if (this.permissionMode === 'acceptEdits' && this.autoApprovable.includes(toolName)) {
            return { allowed: true };
        }

        // 3) 서버에 승인 요청.
        return this.askServer(toolName, input);
    }

    private async askServer(toolName: string, input: unknown): Promise<PermissionDecision> {
        const requestKey = randomUUID();

        let created;
        try {
            created = await this.api.requestPermission(this.jobId, requestKey, toolName, input);
        } catch (error) {
            // 서버에 물을 수 없으면 거부한다. 확인되지 않은 실행을 통과시키지 않는다.
            log('error', '승인 요청 실패 — 거부로 처리합니다.', { jobId: this.jobId, error: String(error) });

            return { allowed: false, reason: '서버에 승인을 요청할 수 없어 거부했습니다.' };
        }

        if (created.decision.behavior !== 'pending') {
            return toDecision(created.decision);
        }

        this.hooks.onWaiting();
        await this.api.quiet('status waiting_permission', () =>
            this.api.status(this.jobId, 'waiting_permission'),
        );

        const decision = await this.waitForDecision(requestKey);

        this.hooks.onResumed();

        return decision;
    }

    /**
     * Reverb 이벤트를 기다리되, 유실 대비로 /inbox 를 주기적으로 폴링한다.
     * 둘 중 먼저 오는 쪽이 이긴다.
     */
    private waitForDecision(requestKey: string): Promise<PermissionDecision> {
        return new Promise<PermissionDecision>((resolve) => {
            let settled = false;

            const finish = (decision: PermissionDecision) => {
                if (settled) return;
                settled = true;
                clearInterval(timer);
                this.waiting.delete(requestKey);
                resolve(decision);
            };

            this.waiting.set(requestKey, finish);

            const timer = setInterval(() => {
                void this.api
                    .inbox(this.jobId)
                    .then((inbox) => {
                        const hit = inbox.permissions.find((p) => p.request_key === requestKey);

                        if (hit && hit.status !== 'pending') {
                            finish({
                                allowed: hit.status === 'allowed',
                                reason: hit.deny_reason ?? (hit.status === 'expired' ? 'timeout' : undefined),
                            });
                        }

                        if (inbox.cancel_requested || inbox.cost_over_limit) {
                            finish({ allowed: false, reason: '작업이 중단되었습니다.' });
                        }
                    })
                    .catch(() => undefined);
            }, config.inboxPollSec * 1000);
        });
    }
}

function toDecision(decision: { behavior: string; message?: string }): PermissionDecision {
    return decision.behavior === 'allow'
        ? { allowed: true }
        : { allowed: false, reason: decision.message ?? '사용자가 거부했습니다.' };
}
