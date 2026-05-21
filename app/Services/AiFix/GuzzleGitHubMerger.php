<?php

namespace App\Services\AiFix;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * GitHub REST API 로 PR 생성 + auto merge.
 *
 * 흐름:
 *   1) worktree 안에서 git add + commit + push origin <branch>
 *   2) POST /repos/{owner}/{repo}/pulls   (PR 생성)
 *   3) PUT  /repos/{owner}/{repo}/pulls/{n}/merge   (squash/merge/rebase)
 *
 * 안전: 각 단계 실패 시 throw 하지 않고 MergeResult(merged=false, error=...) 반환.
 * Personal Access Token 또는 GitHub App token 둘 다 호환 (Bearer auth).
 *
 * 활성화: AI_FIX_MERGER_DRIVER=github + AI_FIX_MERGER_GITHUB_TOKEN=ghp_...
 */
final class GuzzleGitHubMerger implements GitHubMerger
{
    public function __construct(
        private readonly string $token,
        private readonly string $owner       = 'Jack-Won-Prompt',
        private readonly string $repo        = 'supportworks',
        private readonly string $mergeMethod = 'squash',
        private readonly int    $timeout     = 60,
    ) {}

    public function mergeBranch(
        string  $branch,
        string  $target,
        string  $commitTitle,
        string  $commitBody = '',
        ?string $worktreePath = null,
    ): MergeResult {
        if (empty($worktreePath) || !is_dir($worktreePath)) {
            return new MergeResult(
                merged: false,
                error:  'worktreePath required (and must exist) for GuzzleGitHubMerger',
            );
        }

        // ── 1) git add + commit + push ─────────────────────────────────────
        try {
            $this->git(['add', '-A'], $worktreePath);
            // 변경 없으면 commit 자체가 실패하므로 --allow-empty 로 PR 가능하게.
            $commitMsg = $commitTitle . ($commitBody ? "\n\n" . $commitBody : '');
            $this->git(['commit', '-m', $commitMsg, '--allow-empty'], $worktreePath);
            $this->git(['push', 'origin', $branch], $worktreePath);
        } catch (\Throwable $e) {
            return new MergeResult(
                merged: false,
                error:  'git push failed: ' . $e->getMessage(),
            );
        }

        // ── 2) PR 생성 ─────────────────────────────────────────────────────
        try {
            $resp = Http::timeout($this->timeout)
                ->withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->post("https://api.github.com/repos/{$this->owner}/{$this->repo}/pulls", [
                    'title' => $commitTitle,
                    'body'  => $commitBody,
                    'head'  => $branch,
                    'base'  => $target,
                ]);
        } catch (\Throwable $e) {
            return new MergeResult(merged: false, error: 'PR create HTTP threw: ' . $e->getMessage());
        }

        if (!$resp->successful()) {
            Log::warning('[GuzzleGitHubMerger] PR create HTTP ' . $resp->status() . ': ' . mb_substr($resp->body(), 0, 500));
            return new MergeResult(
                merged: false,
                error:  'PR create HTTP ' . $resp->status() . ': ' . mb_substr($resp->body(), 0, 200),
            );
        }

        $prNumber = (int)    $resp->json('number');
        $prUrl    = (string) $resp->json('html_url');

        // ── 3) PR merge ────────────────────────────────────────────────────
        try {
            $merge = Http::timeout($this->timeout)
                ->withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->put("https://api.github.com/repos/{$this->owner}/{$this->repo}/pulls/{$prNumber}/merge", [
                    'commit_title'   => $commitTitle,
                    'commit_message' => $commitBody,
                    'merge_method'   => $this->mergeMethod,
                ]);
        } catch (\Throwable $e) {
            return new MergeResult(
                merged:   false,
                prNumber: $prNumber,
                prUrl:    $prUrl,
                error:    'merge HTTP threw: ' . $e->getMessage(),
            );
        }

        if (!$merge->successful()) {
            Log::warning('[GuzzleGitHubMerger] PR #' . $prNumber . ' merge HTTP ' . $merge->status() . ': ' . mb_substr($merge->body(), 0, 500));
            return new MergeResult(
                merged:   false,
                prNumber: $prNumber,
                prUrl:    $prUrl,
                error:    'merge HTTP ' . $merge->status() . ': ' . mb_substr($merge->body(), 0, 200),
            );
        }

        return new MergeResult(
            merged:    true,
            prNumber:  $prNumber,
            prUrl:     $prUrl,
            mergedSha: (string) $merge->json('sha'),
        );
    }

    private function git(array $args, string $cwd): void
    {
        $proc = new Process(array_merge(['git'], $args), $cwd);
        $proc->setTimeout(120);
        $proc->mustRun();
    }
}
