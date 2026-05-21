<?php

namespace App\Services\AiFix;

/**
 * AI Fix 브랜치를 GitHub PR 로 만들고 master 에 머지.
 *
 * 구현체:
 *   - StubGitHubMerger (테스트/PoC) — 실제 GitHub API 호출 없음
 *   - (추후) GuzzleGitHubMerger — GitHub REST API (POST /pulls + PUT /pulls/{n}/merge)
 *     호출. App 토큰은 config/services.php / .env (GITHUB_AIFIX_TOKEN).
 *
 * 흐름:
 *   1) POST /repos/{owner}/{repo}/pulls  (head=branch, base=target)
 *   2) PUT  /repos/{owner}/{repo}/pulls/{number}/merge (method=squash)
 *   3) merged SHA 반환
 *
 * 실패 시 throws 하지 말고 MergeResult(merged=false, error=...) 반환.
 * 예외는 정말 예측 못 한 시스템 오류(네트워크 끊김 등) 만.
 */
interface GitHubMerger
{
    /**
     * branch 의 변경을 origin 에 push + GitHub PR 생성 + merge.
     *
     * worktreePath: AI 가 수정한 코드가 있는 worktree 의 경로. 구현체가 그 안에서
     * `git add + commit + push` 까지 책임. null 이면 Stub 또는 push 책임 외부.
     */
    public function mergeBranch(
        string $branch,
        string $target,
        string $commitTitle,
        string $commitBody = '',
        ?string $worktreePath = null,
    ): MergeResult;
}
