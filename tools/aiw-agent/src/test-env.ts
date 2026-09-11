/**
 * 단위 테스트용 환경변수 기본값.
 *
 * config.ts 는 필수 환경변수가 없으면 즉시 throw 한다. 테스트가 운영자의 실제
 * .env 에 의존하면 PC 마다 결과가 달라지므로, 테스트 모듈보다 먼저 임포트해
 * 값을 고정한다. dotenv 는 이미 설정된 값을 덮어쓰지 않으므로 .env 가 있어도
 * 여기서 정한 값이 이긴다.
 */
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const DEFAULTS: Record<string, string> = {
    // 테스트가 운영 로그 디렉터리를 어지럽히지 않게 임시 경로로 돌린다.
    LOG_DIR: join(tmpdir(), 'aiw-agent-test-logs'),
    SW_BASE_URL: 'http://localhost',
    SW_AGENT_TOKEN: 'test-token',
    REVERB_APP_KEY: 'test-key',
    REVERB_HOST: 'localhost',
    REVERB_PORT: '8080',
    HANDOVER_DIR: 'docs/aiw/handover',
    CONTEXT_HANDOVER_RATIO: '0.6',
};

for (const [key, value] of Object.entries(DEFAULTS)) {
    process.env[key] ??= value;
}

export {};
