import { realpath } from 'node:fs/promises';
import { ApiClient } from './api.js';
import { config } from './config.js';
import { GitWorkspace } from './git.js';
import type { JobManager } from './job-manager.js';
import { log } from './logger.js';

/** 서버가 보내는 커밋·푸시 요청. */
export interface PublishRequest {
    publish_id: number;
    job_id: number;
    /** 프로젝트별로 나눠 띄운 프로세스가 남의 요청을 걸러내는 값. */
    project_id?: number;
    local_path: string;
    source_branch: string;
    target_branch: string;
    commit_message: string;
}

/**
 * 커밋·머지·푸시 실행기.
 *
 * 이 일은 Claude 가 아니라 데몬이 한다. 샌드박스는 Claude 의 툴 호출을 통제하는
 * 것이고(그래서 Claude 는 여전히 push 할 수 없다), 사람이 화면에서 누른 버튼은
 * 다른 신뢰 경로다.
 *
 * 같은 폴더에서 둘이 동시에 git 을 돌리면 index.lock 이 충돌한다. 같은 요청이
 * 두 번 오는 것은 여기서 막고(Reverb 재전송·폴링 중복 대비), **작업 쪽 git 과의
 * 충돌은 JobManager 의 폴더 큐에 실어 막는다.**
 *
 * 요청 중복만 막는 것으로는 부족했다. 완료 보고가 변경 파일과 diff 를 모으는
 * 동안 커밋·푸시가 끼어들어 index.lock 이 부딪혔고, 푸시는 성공했는데 기록은
 * 실패로 남았다. 화면에는 "진행 중" 인 채로 멈춰 보였다.
 */
export class Publisher {
    private readonly inFlight = new Set<number>();

    constructor(
        private readonly api: ApiClient,
        private readonly jobs: JobManager,
    ) {}

    handle(request: PublishRequest): void {
        // 토큰이 PC 당 하나라 채널도 하나다. 걸러 주지 않으면 프로젝트별로 띄운
        // 프로세스가 **모두** 같은 저장소에서 커밋을 시도해 index.lock 이 부딪힌다.
        // 하나만 성공하고 나머지는 실패로 남아, 성공한 일이 실패로 보고됐다.
        if (config.projectId !== null && Number(request.project_id) !== config.projectId) {
            return;
        }

        if (this.inFlight.has(request.publish_id)) {
            return;
        }

        this.inFlight.add(request.publish_id);

        void this.run(request).finally(() => this.inFlight.delete(request.publish_id));
    }

    private async run(request: PublishRequest): Promise<void> {
        const { publish_id: id, job_id: jobId } = request;

        log('info', '커밋·푸시 요청 수신', {
            publishId: id,
            jobId,
            branch: request.source_branch,
        });

        await this.api.quiet('publish running', () =>
            this.api.publishResult(jobId, id, { status: 'running' }),
        );

        let root: string;

        try {
            root = await realpath(request.local_path);
        } catch {
            await this.report(jobId, id, 'failed', `작업 폴더를 찾을 수 없습니다: ${request.local_path}`);

            return;
        }

        const git = new GitWorkspace(root);

        if (!(await git.isRepo())) {
            await this.report(jobId, id, 'failed', `git 저장소가 아닙니다: ${root}`);

            return;
        }

        try {
            // 같은 폴더의 작업 git 과 한 줄로 세운다. 끼어들면 index.lock 이 부딪힌다.
            const result = await this.jobs.runInFolder(root, () => git.publish({
                sourceBranch: request.source_branch,
                targetBranch: request.target_branch,
                commitMessage: request.commit_message,
                onProgress: (line) => log('info', `[publish ${id}] ${line}`),
            }));

            await this.report(jobId, id, 'succeeded', result.output, result.commitSha);
            log('info', '커밋·푸시 완료', { publishId: id, jobId, commit: result.commitSha });
        } catch (error) {
            const reason = String((error as Error)?.message ?? error);

            await this.report(jobId, id, 'failed', reason);
            log('error', '커밋·푸시 실패', { publishId: id, jobId, reason });
        }
    }

    private async report(
        jobId: number,
        publishId: number,
        status: 'succeeded' | 'failed',
        output: string,
        commitSha: string | null = null,
    ): Promise<void> {
        await this.api.quiet('publish result', () =>
            this.api.publishResult(jobId, publishId, {
                status,
                output,
                ...(commitSha ? { commit_sha: commitSha } : {}),
            }),
        );
    }
}
