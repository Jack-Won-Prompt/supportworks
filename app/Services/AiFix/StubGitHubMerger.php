<?php

namespace App\Services\AiFix;

/**
 * PoC/테스트용 GitHubMerger. 실제 GitHub API 호출 없이 성공만 시뮬레이션.
 *
 * - shouldFail=true 로 생성하면 failure 분기 검증 가능.
 * - 머지된 SHA 는 'stub-<branch>-<rand>' 형태.
 */
final class StubGitHubMerger implements GitHubMerger
{
    public function __construct(
        private readonly bool $shouldFail = false,
        private readonly ?string $failureReason = null,
    ) {}

    public function mergeBranch(
        string $branch,
        string $target,
        string $commitTitle,
        string $commitBody = '',
        ?string $worktreePath = null,
    ): MergeResult {
        if ($this->shouldFail) {
            return new MergeResult(
                merged: false,
                error:  $this->failureReason ?? 'stub merge failure',
            );
        }

        $sha   = 'stub' . substr(md5($branch . microtime(true)), 0, 7);
        $pr    = random_int(100, 9999);

        return new MergeResult(
            merged:    true,
            prNumber:  $pr,
            prUrl:     "https://github.com/example/repo/pull/{$pr}",
            mergedSha: $sha,
        );
    }
}
