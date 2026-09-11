import { ApiClient, JobSpec } from './api.js';
import { config } from './config.js';
import { JobManager } from './job-manager.js';
import { log } from './logger.js';

/**
 * 재기동 복구.
 *
 * 세션은 프로세스와 함께 사라졌으므로 그대로 이어갈 수 없다. 서버는 아직
 * running 으로 알고 있으므로, 여기서 아무것도 하지 않으면 그 job 은 아무도
 * 돌보지 않는 고아가 된다(스케줄러의 reap 이 뒤늦게 실패시킬 때까지).
 * 실제로 그렇게 방치되던 버그가 있었다 — 주석은 resume 을 설명하는데 코드는
 * resumeOnRestart 가 꺼져 있을 때만 동작해서, 기본 설정에서 루프가 아무 일도
 * 하지 않았다.
 *
 * interactive 는 resume_session_id 로 이어붙이기를 시도하고, batch 는 즉시 실패
 * 처리한다 — resume 이 실패하면 SDK 가 새 세션으로 지시를 처음부터 다시 실행해
 * 같은 부작용이 두 번 일어날 수 있기 때문이다.
 */
export async function recoverActiveJobs(
    api: ApiClient,
    jobs: JobManager,
    activeJobIds: number[],
): Promise<void> {
    if (activeJobIds.length === 0) {
        return;
    }

    const abandon = async (jobId: number, reason: string) => {
        log('info', '재기동 복구 — 실패 처리', { jobId, reason });
        await api.quiet('fail', () =>
            api.fail(jobId, reason, { error_code: 'daemon_restarted' }),
        );
    };

    if (!config.resumeOnRestart) {
        for (const jobId of activeJobIds) {
            await abandon(jobId, '데몬이 재시작되어 세션을 이어갈 수 없습니다. 후속 지시로 진행하세요.');
        }

        return;
    }

    // 활성 job 의 스펙을 받아 온다. 스펙에 resume_session_id 가 실려 있다.
    let specs: JobSpec[] = [];

    try {
        ({ jobs: specs } = await api.pendingJobs(true));
    } catch (error) {
        log('error', '재기동 복구용 스펙을 받지 못했습니다.', { error: String(error) });
    }

    const byId = new Map(specs.map((s) => [s.job_id, s]));

    for (const jobId of activeJobIds) {
        const spec = byId.get(jobId);

        if (!spec) {
            await abandon(jobId, '데몬 재시작 후 작업 정보를 받지 못해 이어갈 수 없습니다.');

            continue;
        }

        if (spec.mode === 'batch') {
            await abandon(jobId, '데몬이 재시작되었습니다. 단발 작업은 중복 실행을 피하기 위해 이어가지 않습니다.');

            continue;
        }

        log('info', '재기동 복구 — 이어서 실행', { jobId, resume: Boolean(spec.resume_session_id) });
        jobs.enqueue(spec);
    }
}
