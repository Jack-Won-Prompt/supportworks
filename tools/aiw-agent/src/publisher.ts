import { realpath } from 'node:fs/promises';
import { ApiClient } from './api.js';
import { GitWorkspace } from './git.js';
import { log } from './logger.js';

/** 서버가 보내는 커밋·푸시 요청. */
export interface PublishRequest {
    publish_id: number;
    job_id: number;
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
 * 같은 폴더에서 둘이 동시에 git 을 돌리면 index.lock 이 충돌한다. 한 번에 하나만
 * 처리하고, 이미 처리 중인 요청은 무시한다(Reverb 재전송·폴링 중복 대비).
 */
export class Publisher {
    private readonly inFlight = new Set<number>();

    constructor(private readonly api: ApiClient) {}

    handle(request: PublishRequest): void {
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
            const result = await git.publish({
                sourceBranch: request.source_branch,
                targetBranch: request.target_branch,
                commitMessage: request.commit_message,
                onProgress: (line) => log('info', `[publish ${id}] ${line}`),
            });

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
