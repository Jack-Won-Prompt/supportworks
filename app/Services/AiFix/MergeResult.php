<?php

namespace App\Services\AiFix;

/**
 * GitHubMerger 의 결과. AiFixJob.test_result['merge'] 등에 기록.
 */
final class MergeResult
{
    public function __construct(
        /** 머지가 master 에 반영됐는지 */
        public readonly bool $merged,

        /** 생성된 PR 번호 (실패해도 PR 까지는 만들어졌으면 채워짐) */
        public readonly ?int $prNumber = null,

        /** PR 페이지 URL — 관리자 감사·롤백 추적용 */
        public readonly ?string $prUrl = null,

        /** 머지된 commit SHA. 운영 서버에 배포될 대상 커밋. */
        public readonly ?string $mergedSha = null,

        /** 실패 시 사람이 읽을 수 있는 사유 한 줄 */
        public readonly ?string $error = null,
    ) {}

    public function toArray(): array
    {
        return [
            'merged'     => $this->merged,
            'pr_number'  => $this->prNumber,
            'pr_url'     => $this->prUrl,
            'merged_sha' => $this->mergedSha,
            'error'      => $this->error,
        ];
    }
}
