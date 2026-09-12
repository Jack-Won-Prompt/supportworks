import { createRequire } from 'node:module';
import type { Channel } from 'pusher-js';
import { ApiClient } from './api.js';
import { config } from './config.js';
import { JobManager } from './job-manager.js';
import { log } from './logger.js';
import { Deployer } from './deployer.js';
import { Publisher } from './publisher.js';

/**
 * Reverb 구독.
 *
 * WebSocket 을 신뢰하지 않는다 — 재접속할 때마다 /jobs/pending 과 활성 job 의
 * /inbox 를 다시 훑어 누락을 메운다. 이벤트는 "빠른 길"이고 폴링이 "정확한 길"이다.
 */
// pusher-js 는 CJS 로 배포되고 index.d.ts 는 타입만 re-export 한다(클래스 default export 가 없다).
// 값은 require 로 가져오고, 타입은 실제로 쓰는 표면만 좁혀서 선언한다 —
// 내부 경로(types/src/core/pusher)는 패키지 구조에 묶여 있어 깨지기 쉽다.
interface PusherClient {
    connection: { bind(event: string, callback: (payload?: unknown) => void): void };
    subscribe(channel: string): Channel;
    disconnect(): void;
}

const require = createRequire(import.meta.url);
const Pusher = require('pusher-js') as new (key: string, options: unknown) => PusherClient;

export class Realtime {
    private pusher: PusherClient | null = null;

    constructor(
        private readonly api: ApiClient,
        private readonly jobs: JobManager,
        private readonly agentId: number,
        private readonly onReconnect: () => void,
        private readonly publisher: Publisher,
        private readonly deployer: Deployer,
    ) {}

    connect(): void {
        // cluster 를 지정하면 pusher-js 가 자체 호스트로 붙으려 한다. wsHost 를 반드시 명시한다.
        this.pusher = new Pusher(config.reverb.key, {
            wsHost: config.reverb.host,
            wsPort: config.reverb.port,
            wssPort: config.reverb.port,
            forceTLS: config.reverb.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            cluster: '',
            authEndpoint: `${config.baseUrl}/api/aiw/broadcasting/auth`,
            auth: { headers: { Authorization: `Bearer ${config.token}` } },
        } as any);

        this.pusher.connection.bind('connected', () => {
            log('info', 'Reverb 연결됨');
            this.onReconnect();
        });

        this.pusher.connection.bind('error', (error: unknown) => {
            log('warn', 'Reverb 연결 오류', { error: String(error) });
        });

        this.pusher.connection.bind('disconnected', () => {
            log('warn', 'Reverb 연결 끊김 — 재접속을 기다립니다.');
        });

        const channel: Channel = this.pusher.subscribe(`private-aiw.agent.${this.agentId}`);

        channel.bind('pusher:subscription_error', (status: unknown) => {
            log('error', '채널 인가 실패 — 토큰과 REVERB 설정을 확인하세요.', { status: String(status) });
        });

        channel.bind('job.dispatched', (payload: any) => {
            // 토큰이 PC 당 하나라 채널도 하나다. 프로젝트별로 나눠 띄운 프로세스는
            // 남의 일감까지 받으므로 여기서 걸러야 한다. 걸러 주지 않으면 셋이
            // 같은 작업을 동시에 실행한다.
            if (config.projectId !== null && Number(payload?.project_id) !== config.projectId) {
                return;
            }

            log('info', '지시 수신', { jobId: payload?.job_id });
            this.jobs.enqueue(payload);
        });

        channel.bind('publish.requested', (payload: any) => {
            this.publisher.handle(payload);
        });

        channel.bind('deploy.requested', (payload: any) => {
            this.deployer.handle(payload);
        });

        channel.bind('job.user-message', (payload: any) => {
            void this.jobs
                .session(payload?.job_id)
                ?.deliver(payload.message_id, payload.content, payload.attachments ?? []);
        });

        channel.bind('permission.decided', (payload: any) => {
            this.jobs
                .session(payload?.job_id)
                ?.resolvePermission(
                    payload.request_key,
                    payload.status === 'allowed',
                    payload.deny_reason ?? undefined,
                );
        });

        channel.bind('job.cancel-requested', (payload: any) => {
            void this.jobs.session(payload?.job_id)?.requestCancel(payload?.reason);
        });

        channel.bind('job.end-requested', (payload: any) => {
            void this.jobs.session(payload?.job_id)?.requestEnd();
        });

        channel.bind('handover.requested', (payload: any) => {
            void this.jobs.session(payload?.job_id)?.requestHandover();
        });
    }

    disconnect(): void {
        this.pusher?.disconnect();
    }
}
