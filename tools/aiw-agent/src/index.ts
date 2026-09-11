import { platform, release } from 'node:os';
import { ApiClient } from './api.js';
import { config } from './config.js';
import { JobManager } from './job-manager.js';
import { log } from './logger.js';
import { Realtime } from './realtime.js';
import { recoverActiveJobs } from './recover.js';
import { AlreadyRunningError, acquireLock, releaseLock } from './single-instance.js';

async function main(): Promise<void> {
    log('info', 'AI Works 데몬을 시작합니다.', {
        baseUrl: config.baseUrl,
        maxParallelJobs: config.maxParallelJobs,
        // 과금 주체가 갈리는 지점이라 명시적으로 남긴다.
        auth: config.anthropicApiKey ? 'ANTHROPIC_API_KEY' : '이 PC 의 Claude Code 로그인',
    });

    if (!config.anthropicApiKey) {
        // 경고가 아니다 — 구독 로그인으로 돌리는 것이 정상 운영 모드일 수 있다.
        // 다만 이 모드에서는 서버에 보고되는 cost_usd 가 실제 청구액이 아니라
        // "API 로 썼다면 얼마였을지"의 추정치라는 점을 분명히 해 둔다.
        log('info', '이 PC 의 Claude Code 로그인으로 실행합니다. 보고되는 비용은 실제 청구액이 아니라 추정치입니다.');
    }

    // 여러 인스턴스가 같은 지시를 받으면 같은 폴더에서 git 이 충돌한다.
    try {
        await acquireLock();
    } catch (error) {
        if (error instanceof AlreadyRunningError) {
            log('error', error.message);
            process.exit(1);
        }

        throw error;
    }

    const api = new ApiClient();
    const jobs = new JobManager(api);

    const capabilities = {
        max_parallel_jobs: config.maxParallelJobs,
        os: `${platform()} ${release()}`,
        node: process.version,
        adapter: 'sdk',
        shell: config.shell ?? null,
        /**
         * 과금 주체. 화면이 비용 라벨을 고르는 데 쓴다.
         * subscription 모드에서 보고되는 cost_usd 는 실제 청구액이 아니라
         * "API 로 썼다면 얼마였을지"의 추정치다.
         */
        auth_mode: config.anthropicApiKey ? 'api_key' : 'subscription',
    };

    // 첫 하트비트로 토큰을 검증하고 밀린 작업을 받아 온다.
    let first;
    try {
        first = await api.heartbeat(capabilities);
    } catch (error) {
        log('error', '서버에 연결할 수 없습니다. SW_BASE_URL 과 SW_AGENT_TOKEN 을 확인하세요.', {
            error: String(error),
        });
        process.exit(1);
    }

    log('info', '서버 연결됨', {
        pending: first.pending_job_ids.length,
        active: first.active_job_ids.length,
    });

    await recoverActiveJobs(api, jobs, first.active_job_ids);

    // agent_id 는 하트비트가 알려준다 — 사람이 화면에서 번호를 옮겨 적을 필요가 없다.
    const agentId = first.agent_id;

    const sync = async () => {
        try {
            const { jobs: pending } = await api.pendingJobs();

            for (const spec of pending) {
                jobs.enqueue(spec);
            }

            // 활성 job 의 미전달 메시지·결정을 보충한다(Reverb 유실 대비).
            for (const jobId of jobs.activeJobIds()) {
                const inbox = await api.inbox(jobId);
                const session = jobs.session(jobId);

                for (const message of inbox.messages) {
                    await session?.deliver(
                        message.message_id,
                        message.content,
                        message.attachments ?? [],
                    );
                }

                for (const permission of inbox.permissions) {
                    if (permission.status !== 'pending') {
                        session?.resolvePermission(
                            permission.request_key,
                            permission.status === 'allowed',
                            permission.deny_reason ?? undefined,
                        );
                    }
                }
            }
        } catch (error) {
            log('warn', '동기화 실패 — 다음 하트비트에 다시 시도합니다.', { error: String(error) });
        }
    };

    const realtime = new Realtime(api, jobs, agentId, () => void sync());
    realtime.connect();

    await sync();

    const timer = setInterval(() => {
        void api
            .heartbeat(capabilities)
            .then(({ pending_job_ids }) => {
                if (pending_job_ids.length) {
                    void sync();
                }
            })
            .catch((error) => log('warn', '하트비트 실패', { error: String(error) }));
    }, config.heartbeatSec * 1000);

    const shutdown = async (signal: string) => {
        log('info', `${signal} 수신 — 정리 후 종료합니다.`);
        clearInterval(timer);
        realtime.disconnect();

        // 네트워크가 막혀도 매달리지 않도록 시간 제한을 둔다.
        await Promise.race([jobs.shutdown(), new Promise((r) => setTimeout(r, 5000))]);
        await releaseLock();
        process.exit(0);
    };

    process.on('SIGINT', () => void shutdown('SIGINT'));
    process.on('SIGTERM', () => void shutdown('SIGTERM'));
}

void main().catch((error) => {
    log('error', '데몬을 시작할 수 없습니다.', { error: String(error) });
    process.exit(1);
});
