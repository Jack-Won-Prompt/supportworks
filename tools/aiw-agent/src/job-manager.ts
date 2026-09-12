import { realpath } from 'node:fs/promises';
import PQueue from 'p-queue';
import { ApiClient, JobSpec } from './api.js';
import { config } from './config.js';
import { isJobSetupError, JobSetupError } from './errors.js';
import { BranchBase, GitWorkspace } from './git.js';
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

    /** 작업 브랜치가 갈라져 나온 자리. 중단됐을 때 되돌릴 곳이다. */
    private readonly branchBase = new Map<number, BranchBase>();

    constructor(private readonly api: ApiClient) {}

    activeJobIds(): number[] {
        return [...this.active.keys()];
    }

    /** 폴더 키. Windows 는 대소문자를 구분하지 않으므로 낮춰서 맞춘다. */
    private folderKey(root: string): string {
        return process.platform === 'win32' ? root.toLowerCase() : root;
    }

    /**
     * 같은 작업 폴더의 git 작업을 한 줄로 세운다.
     *
     * 작업 실행만 직렬로 두면 부족하다. 완료 보고가 변경 파일과 diff 를 모으는
     * 동안 커밋·푸시가 끼어들면 index.lock 이 부딪힌다 — 실제로 그렇게 깨졌고,
     * 푸시는 성공했는데 기록은 실패로 남았다.
     */
    runInFolder<T>(root: string, task: () => Promise<T>): Promise<T> {
        const key = this.folderKey(root);
        const queue = this.folderQueues.get(key) ?? new PQueue({ concurrency: 1 });

        this.folderQueues.set(key, queue);

        return queue.add(task) as Promise<T>;
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

        const key = this.folderKey(root);
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
                            this.branchBase.delete(spec.job_id);
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
                    // 어디서 갈라져 나왔는지 기억해 둔다. 중단되면 여기로 되돌린다.
                    this.branchBase.set(
                        spec.job_id,
                        await git.prepareBranch(`aiw/job-${spec.job_id}`, spec.default_branch),
                    );
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
            this.branchBase.delete(spec.job_id);

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

        // 중간에 끊긴 작업은 고치다 만 파일을 남긴다. 먼저 정리하고 보고한다 —
        // 보고가 먼저 가면 화면은 '중단됨' 인데 작업 폴더는 아직 어질러진 상태다.
        const restored = await this.restoreWorkspace(spec, root, manager);

        await this.api.quiet('fail', () =>
            this.api.fail(spec.job_id, detail ?? (reason === 'cancelled' ? '취소되었습니다.' : '실패했습니다.'), {
                cost_usd: costUsd,
                duration_ms: durationMs,
            }),
        );

        if (restored) {
            await this.api.quiet('messages', () =>
                this.api.messages(spec.job_id, [{
                    client_key: `restore-${spec.job_id}`,
                    role: 'system',
                    content: restored,
                }]),
            );
        }
    }

    /**
     * 중단된 작업의 작업 폴더를 수정 이전 상태로 되돌린다.
     *
     * 브랜치 분리를 켠 작업만 손댄다. 끈 작업은 시작 시점에 폴더가 깨끗했다는
     * 보장이 없어, 되돌리면 사람이 하던 작업까지 지운다.
     *
     * 돌려주는 값은 채팅창에 그대로 띄울 문구다. null 이면 알릴 것이 없다.
     */
    private async restoreWorkspace(
        spec: JobSpec,
        root: string,
        manager: SessionManager,
    ): Promise<string | null> {
        const base = this.branchBase.get(spec.job_id);

        if (!spec.use_branch) {
            return '작업 폴더는 되돌리지 않았습니다 — 브랜치 분리를 끈 작업이라'
                + ' 이 작업의 변경과 원래 있던 변경을 구분할 수 없습니다.'
                + ' 필요하면 작업 PC 에서 직접 확인해 주세요.';
        }

        if (!base) {
            // 저장소가 아니거나 준비 전에 끝났다. 되돌릴 것도 없다.
            return null;
        }

        const branch = `aiw/job-${spec.job_id}`;

        try {
            const result = await new GitWorkspace(root).restoreAfterAbort({
                branch,
                base,
                commitMessage: `wip(aiw): 작업 지시 #${spec.job_id} 중단 시점 보관`,
            });

            switch (result.kind) {
                case 'nothing':
                    return `작업 폴더는 그대로입니다 — 되돌릴 변경이 없었습니다.`
                        + ` (\`${result.baseBranch}\` 브랜치)`;
                case 'skipped':
                    manager.pushLog('daemon', `작업 폴더 복구 건너뜀 — ${result.reason}`);

                    return `작업 폴더를 되돌리지 않았습니다 — ${result.reason}`;
                case 'restored': {
                    const sample = result.files.slice(0, 10).join(', ');
                    const more = result.files.length > 10 ? ` 외 ${result.files.length - 10}건` : '';

                    manager.pushLog('daemon', `작업 폴더를 ${result.baseBranch} 상태로 되돌렸습니다.`);

                    return `작업 폴더를 수정 이전 상태(\`${result.baseBranch}\`)로 되돌렸습니다.`
                        + ` 되돌린 파일 ${result.files.length}개: ${sample}${more}.`
                        + `

중단 시점의 내용은 버리지 않고 \`${branch}\` 브랜치에 남겨 뒀습니다`
                        + `${result.commitSha ? ` (커밋 \`${result.commitSha.slice(0, 8)}\`)` : ''}.`
                        + ' 확인하려면 작업 PC 에서 그 브랜치를 체크아웃하세요.';
                }
            }
        } catch (error) {
            const reason = String((error as Error).message ?? error);

            log('warn', '작업 폴더 복구 실패', { jobId: spec.job_id, error: reason });
            manager.pushLog('error', `작업 폴더 복구 실패: ${reason}`);

            return `작업 폴더를 되돌리지 못했습니다: ${reason}`
                + ' 다음 지시가 시작되지 않을 수 있으니 작업 PC 에서 정리해 주세요.';
        }
    }

    /** SIGINT/SIGTERM. 세션은 프로세스와 함께 사라지므로 서버에 알리고 끝낸다. */
    async shutdown(): Promise<void> {
        const sessions = [...this.active.values()];

        await Promise.allSettled(sessions.map((s) => s.stop('failed', '데몬이 종료되었습니다.')));
    }
}
