import { realpath } from 'node:fs/promises';
import PQueue from 'p-queue';
import { ApiClient, JobSpec } from './api.js';
import { config } from './config.js';
import { isJobSetupError, JobSetupError } from './errors.js';
import { GitWorkspace } from './git.js';
import { log } from './logger.js';
import { SessionManager } from './session-manager.js';
import { SdkSessionAdapter } from './session/sdk-adapter.js';

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

    /** 세션 시작 전에 job 별로 이미 쓴 로그 수. 시퀀스 충돌을 막는다. */
    private readonly preLogCount = new Map<number, number>();

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
                this.api.fail(
                    spec.job_id,
                    '이 담당자에 해당 프로젝트의 로컬 경로가 매핑되어 있지 않습니다.',
                    { error_code: 'no_mapping', error_detail: { project_id: spec.project_id } },
                ),
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
                this.api.fail(
                    spec.job_id,
                    `매핑된 경로를 이 PC 에서 찾을 수 없습니다: ${spec.local_path}`,
                    { error_code: 'path_missing', error_detail: { local_path: spec.local_path } },
                ),
            );
            this.known.delete(spec.job_id);

            return;
        }

        const key = process.platform === 'win32' ? root.toLowerCase() : root;
        const queue = this.folderQueues.get(key) ?? new PQueue({ concurrency: 1 });
        this.folderQueues.set(key, queue);

        if (queue.size > 0 || queue.pending > 0) {
            log('info', '같은 폴더의 작업이 끝나기를 기다립니다.', { jobId: spec.job_id, root });

            // 화면에도 알린다. 이게 없으면 사용자에게는 "보냈는데 아무 일도
            // 일어나지 않는" 상태로 보인다(실제로 그렇게 관측됐다).
            // 세션이 아직 없어 SessionManager 의 로그 경로를 쓸 수 없으므로 직접 쓴다.
            const written = await this.api.quiet('queued log', () =>
                this.api.logs(spec.job_id, [{
                    seq: 0,
                    type: 'daemon',
                    content: `같은 폴더(${root})에서 다른 작업이 실행 중입니다. 끝나면 자동으로 시작합니다.`,
                }]),
            );

            if (written) {
                // 세션 로그가 seq 0 을 다시 쓰면 이 줄이 덮여 사라진다.
                this.preLogCount.set(spec.job_id, 1);
            }
        }

        await queue.add(() => this.global.add(() => this.execute(spec, root)));
    }

    private async execute(spec: JobSpec, root: string): Promise<void> {
        log('info', '작업 시작', { jobId: spec.job_id, root });

        let manager: SessionManager | null = null;

        const finished = new Promise<void>((resolve) => {
            manager = new SessionManager(
                this.api,
                spec,
                {
                    onTerminal: (reason, detail) => {
                        void this.report(spec, root, manager!, reason, detail).finally(() => {
                            this.active.delete(spec.job_id);
                            this.known.delete(spec.job_id);
                            this.preLogCount.delete(spec.job_id);
                            resolve();
                        });
                    },
                },
                () => new SdkSessionAdapter(),
                this.preLogCount.get(spec.job_id) ?? 0,
            );
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
            const setup: JobSetupError | null = isJobSetupError(error) ? error : null;
            const reason = String((error as Error).message ?? error);

            // 콘솔에도 남긴다. 이게 없으면 서버에만 실패가 기록되고 데몬 화면은
            // "작업 시작" 에서 멈춘 것처럼 보여, 멈춘 건지 실패한 건지 알 수 없다.
            log('error', '작업을 시작하지 못했습니다.', { jobId: spec.job_id, code: setup?.code, reason });

            await this.api.quiet('fail', () =>
                this.api.fail(spec.job_id, reason, setup
                    ? { error_code: setup.code, error_detail: setup.detail }
                    : {}),
            );
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
