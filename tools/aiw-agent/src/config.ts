import 'dotenv/config';

function required(name: string): string {
    const value = process.env[name];

    if (!value) {
        throw new Error(`필수 환경변수가 없습니다: ${name}. .env.example 을 참고하세요.`);
    }

    return value;
}

function num(name: string, fallback: number): number {
    const raw = process.env[name];
    const parsed = raw ? Number(raw) : NaN;

    return Number.isFinite(parsed) ? parsed : fallback;
}

function bool(name: string, fallback: boolean): boolean {
    const raw = process.env[name];

    return raw === undefined ? fallback : /^(1|true|yes|on)$/i.test(raw);
}

export const config = {
    baseUrl: required('SW_BASE_URL').replace(/\/+$/, ''),
    token: required('SW_AGENT_TOKEN'),

    reverb: {
        key: required('REVERB_APP_KEY'),
        host: required('REVERB_HOST'),
        port: num('REVERB_PORT', 443),
        scheme: process.env.REVERB_SCHEME ?? 'https',
    },

    /**
     * Windows 에서 Claude Code 는 Git Bash 를 요구한다. 이 값을
     * CLAUDE_CODE_GIT_BASH_PATH 로 SDK 자식 프로세스에 넘긴다.
     * 데몬이 직접 셸을 띄우지는 않는다.
     */
    shell: process.env.SW_SHELL || undefined,

    /**
     * 이 프로세스가 맡은 프로젝트. 비우면 이 PC 의 모든 프로젝트를 맡는다.
     *
     * 프로젝트마다 프로세스를 나눠 띄우면 하나가 죽어도 나머지는 계속 돈다.
     * 그때 이 값이 없으면 셋이 같은 일감을 동시에 집어가 같은 폴더에서
     * git 이 부딪힌다. 토큰은 PC 당 하나이므로 구분은 이 값으로만 된다.
     */
    projectId: process.env.SW_PROJECT_ID ? Number(process.env.SW_PROJECT_ID) : null,

    heartbeatSec: num('HEARTBEAT_SEC', 30),

    /**
     * PC 전체 동시 실행 상한. 같은 local_path 는 이 값과 무관하게 항상 직렬이다
     * (브랜치 충돌은 같은 워킹트리 안에서만 일어나므로 직렬 기준은 PC 가 아니라 폴더다).
     */
    maxParallelJobs: num('MAX_PARALLEL_JOBS', 2),

    jobTimeoutSec: num('JOB_TIMEOUT_SEC', 1800),
    idleTimeoutSec: num('IDLE_TIMEOUT_SEC', 300),
    sessionMaxSec: num('SESSION_MAX_SEC', 7200),

    /** context_limit_tokens 대비 이 비율에 도달하면 인수인계로 세션을 교체한다. */
    contextHandoverRatio: num('CONTEXT_HANDOVER_RATIO', 0.6),

    handoverDir: process.env.HANDOVER_DIR ?? 'docs/aiw/handover',
    logDir: process.env.LOG_DIR ?? './logs',

    resumeOnRestart: bool('RESUME_ON_RESTART', true),

    /** 승인 결정을 기다리는 동안 /inbox 를 폴링하는 주기. Reverb 유실 대비. */
    inboxPollSec: num('INBOX_POLL_SEC', 30),

    /**
     * Anthropic 자격.
     *
     * 값이 있으면 Claude Code 가 이 키를 쓰고(= 키 소유 계정으로 과금),
     * 비어 있으면 이 PC 의 Claude Code 로그인(~/.claude.json)을 쓴다
     * (= PC 사용자 계정으로 과금). 어느 쪽인지 기동 로그에 남긴다.
     */
    anthropicApiKey: process.env.ANTHROPIC_API_KEY || undefined,
} as const;

export type Config = typeof config;
