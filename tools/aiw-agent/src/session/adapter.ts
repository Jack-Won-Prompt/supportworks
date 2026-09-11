import type { PermissionDecision } from '../permissions.js';

/** 한 턴이 끝날 때 집계되는 값. */
export interface TurnUsage {
    /**
     * 현재 컨텍스트 크기 = 그 턴 마지막 assistant 메시지의
     * input_tokens + cache_read_input_tokens + cache_creation_input_tokens.
     *
     * 누적 합산이 아니다. 입력 토큰은 턴마다 전체 컨텍스트가 재전송되므로
     * 합산하면 실제의 수 배로 부풀어 인수인계가 조기에·반복적으로 터진다.
     */
    contextTokens: number;

    /** SDK 가 주는 누적 비용 추정치. 이건 누적값이므로 그대로 쓴다. */
    cumulativeCostUsd: number;
}

export interface SessionEvents {
    /** system/init 에서 session_id 를 얻었을 때. */
    onSessionId(sessionId: string): void;

    /** assistant 의 텍스트 응답(턴 종료 시 1건). */
    onAssistantText(text: string): void;

    /** 툴 호출·결과·시스템 이벤트 → 서버의 aiw_job_logs 로 간다. */
    onLog(type: 'system' | 'tool_use' | 'tool_result' | 'result' | 'error', content: string, raw?: unknown): void;

    /**
     * 한 턴이 끝났다. 게이지 갱신·인수인계 판정 시점.
     *
     * @param completed batch 에서 작업이 끝났음을 뜻한다. 이때는 인수인계보다
     *                  완료 처리가 우선이다 — 끝난 일을 정리해 넘길 이유가 없다.
     */
    onTurnEnd(usage: TurnUsage, completed?: boolean): void;

    /** 세션이 스스로 끝났다(batch 완료 또는 오류). */
    onFinished(error?: Error): void;
}

export interface SessionStartOptions {
    prompt: string;
    cwd: string;
    model?: string | null;
    /** 이 job 이 쓸 수 있는 툴. 나머지는 컨텍스트에서 제거한다. */
    allowedTools: string[];
    resumeSessionId?: string | null;
    canUseTool(toolName: string, input: unknown): Promise<PermissionDecision>;
}

/**
 * 세션 실행기 추상화.
 *
 * SDK 어댑터가 기본이고, SDK 를 설치할 수 없는 환경에서는 CLI 어댑터로 폴백한다.
 * 두 구현의 능력이 다르므로(CLI 는 승인 미지원) 어느 쪽인지 밖에서 알 수 있어야 한다.
 */
export interface SessionAdapter {
    readonly kind: 'sdk' | 'cli';

    /** 승인 게이트를 지원하는가. CLI 폴백은 false. */
    readonly supportsPermissions: boolean;

    start(options: SessionStartOptions, events: SessionEvents): Promise<void>;

    /** 대화형: 실행 중 사용자 메시지 주입. */
    send(text: string): void;

    /** 진행 중인 턴을 끊는다. */
    interrupt(): void;

    /** 세션 종료. */
    close(): Promise<void>;
}
