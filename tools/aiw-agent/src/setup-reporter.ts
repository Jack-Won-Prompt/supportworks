import { ApiClient } from './api.js';
import { config } from './config.js';
import { log } from './logger.js';
import { checkSetup, cleanupWorkspace, MappingSpec } from './workspace.js';

/**
 * 화면에서 온 '다시 점검' · '작업 정리' 요청을 처리한다.
 *
 * 이 프로세스가 맡은 프로젝트만 손댄다. 프로젝트마다 데몬을 따로 띄우므로,
 * 걸러 내지 않으면 넷이 같은 폴더를 동시에 만지게 된다 — publish 에서 실제로
 * 그렇게 index.lock 이 충돌했다.
 */
export class SetupReporter {
    constructor(private readonly api: ApiClient) {}

    /** 요청받은 프로젝트가 이 프로세스 담당인지. */
    private mine(projectId: number): boolean {
        return config.projectId === null || config.projectId === projectId;
    }

    private async mapping(projectId: number): Promise<MappingSpec | null> {
        const res = await this.api.quiet('mappings', () => this.api.mappings());
        const found = res?.mappings?.find((m) => m.project_id === projectId);

        return found
            ? { project_id: found.project_id, local_path: found.local_path, default_branch: found.default_branch }
            : null;
    }

    async recheck(projectId: number): Promise<void> {
        if (!this.mine(projectId)) {
            return;
        }

        const mapping = await this.mapping(projectId);

        if (!mapping) {
            log('warn', '점검 요청을 받았지만 매핑을 찾지 못했습니다.', { projectId });

            return;
        }

        const result = await checkSetup(mapping);

        log('info', '점검 결과를 보고합니다.', { projectId, status: result.status });
        await this.api.quiet('setup', () => this.api.reportSetup(projectId, result.status, result.message));
    }

    async cleanup(projectId: number): Promise<void> {
        if (!this.mine(projectId)) {
            return;
        }

        const mapping = await this.mapping(projectId);

        if (!mapping) {
            log('warn', '정리 요청을 받았지만 매핑을 찾지 못했습니다.', { projectId });

            return;
        }

        const result = await cleanupWorkspace(mapping);

        log('info', '정리 결과를 보고합니다.', { projectId, branch: result.branch });

        // 정리 결과를 사람이 읽을 수 있게 그대로 싣는다. 상태가 ok 여도 무엇을
        // 했는지(어느 브랜치에 보관했는지) 알려야 한다.
        await this.api.quiet('setup', () =>
            this.api.reportSetup(
                projectId,
                result.setup.status,
                result.setup.status === 'ok' ? result.message : `${result.message} (${result.setup.message ?? ''})`,
            ),
        );
    }
}
