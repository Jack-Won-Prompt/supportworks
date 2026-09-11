/**
 * 실패 사유 코드.
 *
 * 서버의 App\Enums\AiWork\AiwFailureCode 와 같은 문자열을 쓴다. 서버가 화이트리스트로
 * 검증하므로 한쪽만 바꾸면 조용히 무시되는 대신 422 로 드러난다.
 */
export type FailureCode =
    | 'dirty_tree'
    | 'missing_branch'
    | 'no_mapping'
    | 'path_missing'
    | 'daemon_restarted';

/**
 * 화면이 복구 버튼을 띄울 수 있는 실패.
 *
 * 문자열 메시지만 보내면 화면은 "무엇을 할 수 있는지" 판단할 수 없다. detail 에는
 * 그 판단에 필요한 값(걸린 파일 목록, 있는 브랜치 목록)을 담는다.
 */
export class JobSetupError extends Error {
    constructor(
        readonly code: FailureCode,
        message: string,
        readonly detail: Record<string, unknown> = {},
    ) {
        super(message);
        this.name = 'JobSetupError';
    }
}

export function isJobSetupError(error: unknown): error is JobSetupError {
    return error instanceof JobSetupError;
}
