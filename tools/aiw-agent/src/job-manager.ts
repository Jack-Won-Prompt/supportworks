import { realpath } from 'node:fs/promises';
import PQueue from 'p-queue';
import { ApiClient, JobSpec } from './api.js';
import { config } from './config.js';
import { GitWorkspace } from './git.js';
import { log } from './logger.js';
import { SessionManager } from './session-manager.js';

/**
 * 실행 큐와 동시성.
 *
 * 직렬 기준은 PC 가 아니라 `local_path` 다 — 브랜치 충돌은 같은 워킹트리 안에서만
 * 일어나므로, 다른 프로젝트끼리는 동시에 돌려도 안전하다. 폴더별 큐를 전역
 * 세마포어(MAX_PARALLEL_JOBS)로 감싸 CPU·메모리·API 비용을 보호한다.
 */
export class JobManager {
    private readonly folderQueues = new Map<string, PQueue>();

    private readonly global = new PQueue({ concurrency: config.maxParallelJobs });

    private readonly active = new Map<number, SessionManager>();

    private readonly known = new Set<number>();

    constructor(private readonly api: ApiClient) {}

    activeJobIds(): number[] {
        return [...this.active.keys()];
    }

    session(jobId: number): SessionManager | undefined {
        return this.active.get(jobId);
    }

    /** 중복 수신을 흡수한다. 같은 job 이 Reverb 와 폴링으로 두 번 와도 한 번만 돈다. */
    enqueue(spec: JobSpec): void {
        if (this.known.has(spec.job_id)) {
            return;
        }

        this.known.add(spec.job_id);

        void this.schedule(spec).catch((error) => {
            log('error', 'job 처리 중 예외', { jobId: spec.job_id, error: String(error) });
        });
    }

    private async schedule(spec: JobSpec): Promise<void> {
        if (!spec.local_path) {
            await this.api.quiet('fail', () =>
                this.api.fail(spec.job_id, '이 작업 PC 에 프로젝트 로컬 경로가 매핑되어 있지 않습니다.'),
            );
            this.known.delete(spec.job_id);

            return;
        }

        let root: string;
        try {
            // realpath 로 정규화해야 심볼릭 링크·대소문자 차이로 같은 폴더가
            // 다른 큐에 들어가는 일이 없다.
            root = await realpath(spec.local_path);
        } catch {
            await this.api.quiet('fail', () =>
                this.api.fail(spec.job_id, `로컬 경로를 찾을 수 없습니다: ${spec.local_path}`),
            );
            this.known.delete(spec.job_id);

            return;
        }

        const key = process.platform === 'win32' ? root.toLowerCase() : root;
        const queue = this.folderQueues.get(key) ?? new PQueue({ concurrency: 1 });
        this.folderQueues.set(key, queue);

        if (queue.size > 0 || queue.pending > 0) {
            log('info', '같은 폴더의 작업이 끝나기를 기다립니다.', { jobId: spec.job_id, root });
        }

        await queue.add(() => this.global.add(() => this.execute(spec, root)));
    }

    private async execute(spec: JobSpec, root: string): Promise<void> {
        log('info', '작업 시작', { jobId: spec.job_id, root });

        let manager: SessionManager | null = null;

        const finished = new Promise<void>((resolve) => {
            manager = new SessionManager(this.api, spec, {
                onTerminal: (reason, detail) => {
                    void this.report(spec, root, manager!, reason, detail).finally(() => {
                        this.active.delete(spec.job_id);
                        this.known.delete(spec.job_id);
                        resolve();
                    });
                },
            });
        });

        this.active.set(spec.job_id, manager!);

        try {
            if (spec.use_branch) {
                const git = new GitWorkspace(root);

                if (await git.isRepo()) {
                    await git.prepareBranch(`aiw/job-${spec.job_id}`, spec.default_branch);
                } else {
                    manager!.pushLog('daemon', 'git 저장소가 아니라 브랜치 분리를 건너뜁니다.');
                }
            }

            await manager!.run(root);
        } catch (error) {
            await this.api.quiet('fail', () => this.api.fail(spec.job_id, String((error as Error).message ?? error)));
            this.active.delete(spec.job_id);
            this.known.delete(spec.job_id);

            return;
        }

        await finished;
    }

    private async report(
        spec: JobSpec,
        root: string,
        manager: SessionManager,
        reason: 'completed' | 'failed' | 'cancelled',
        detail?: string,
    ): Promise<void> {
        const { durationMs, costUsd } = manager.metrics;

        if (reason === 'completed') {
            const git = new GitWorkspace(root);
            let changed: string[] = [];
            let diff = '';

            try {
                if (await git.isRepo()) {
                    changed = await git.changedFiles();
                    diff = await git.collectDiff(spec.default_branch);
                }
            } catch (error) {
                log('warn', 'diff 수집 실패', { jobId: spec.job_id, error: String(error) });
            }

            await this.api.quiet('complete', () =>
                this.api.complete(spec.job_id, {
                    result_summary: detail ?? null,
                    changed_files: changed.length ? changed : null,
                    git_diff: diff || null,
                    cost_usd: costUsd,
                    duration_ms: durationMs,
                }),
            );

            return;
        }

        await this.api.quiet('fail', () =>
            this.api.fail(spec.job_id, detail ?? (reason === 'cancelled' ? '취소되었습니다.' : '실패했습니다.'), {
                cost_usd: costUsd,
                duration_ms: durationMs,
            }),
        );
    }

    /** SIGINT/SIGTERM. 세션은 프로세스와 함께 사라지므로 서버에 알리고 끝낸다. */
    async shutdown(): Promise<void> {
        const sessions = [...this.active.values()];

        await Promise.allSettled(sessions.map((s) => s.stop('failed', '데몬이 종료되었습니다.')));
    }
}
