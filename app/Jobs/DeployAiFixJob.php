<?php

namespace App\Jobs;

use App\Models\AiFixJob;
use App\Services\AiFix\AiFixNotifier;
use App\Services\AiFix\DeployResult;
use App\Services\AiFix\GitHubMerger;
use App\Services\AiFix\MergeResult;
use App\Services\AiFix\RemoteDeployer;
use App\Services\AiFix\WorktreeManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 관리자가 [배포 승인] 한 (deploying 상태) AiFixJob 을 받아
 *   1) GitHub PR 생성 + master 머지
 *   2) 운영 서버 SSH 로 deploy.sh 실행
 *   3) 결과에 따라 DEPLOYED / DEPLOY_FAILED / ROLLED_BACK 상태 전이
 *   4) 알림 발송
 *   5) AI Maintenance 워크트리 정리
 *
 * 멱등성: status != DEPLOYING 이면 skip.
 *
 * 실패 처리:
 *   - PR/머지 실패 → DEPLOY_FAILED
 *   - SSH 자체 실패 → DEPLOY_FAILED
 *   - deploy.sh exit 4 (롤백 성공) → ROLLED_BACK
 *   - deploy.sh exit 5 (롤백 실패) → DEPLOY_FAILED + error_message 강하게 표시
 *   - 그 외 non-zero → DEPLOY_FAILED
 */
class DeployAiFixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;   // deploy.sh 자체가 길어질 수 있음 (composer/migrate)
    public int $tries   = 1;     // 멱등성 보장을 위해 자동 재시도 안 함

    public function __construct(public readonly int $aiFixJobId) {}

    public function handle(
        GitHubMerger    $merger,
        RemoteDeployer  $deployer,
        WorktreeManager $worktrees,
        ?AiFixNotifier  $notifier = null,
    ): void {
        $job = AiFixJob::find($this->aiFixJobId);
        if ($job === null) {
            Log::info("[DeployAiFixJob] job #{$this->aiFixJobId} not found");
            return;
        }
        if ($job->status !== AiFixJob::STATUS_DEPLOYING) {
            Log::info("[DeployAiFixJob] job #{$job->id} not in deploying state ({$job->status}) — skip");
            return;
        }

        $notifier ??= app(AiFixNotifier::class);

        // ── 1) GitHub PR 머지 ───────────────────────────────────────────────
        try {
            $merge = $merger->mergeBranch(
                branch:      $job->branch_name ?? "ai-fix/{$job->id}",
                target:      'master',
                commitTitle: "AI Fix #{$job->id}",
                commitBody:  (string) ($job->proposed_fix_summary ?? ''),
            );
        } catch (\Throwable $e) {
            $this->terminate($job, AiFixJob::STATUS_DEPLOY_FAILED,
                "GitHub merge threw: " . $e->getMessage(), $notifier);
            return;
        }
        if (!$merge->merged) {
            $this->terminate($job, AiFixJob::STATUS_DEPLOY_FAILED,
                "merge rejected: " . ($merge->error ?? '(unknown)'), $notifier,
                extra: ['merge' => $merge->toArray()],
            );
            return;
        }
        Log::info("[DeployAiFixJob] job #{$job->id} merged: {$merge->mergedSha} (PR {$merge->prUrl})");

        // ── 2) 운영 서버 deploy.sh 실행 ─────────────────────────────────────
        try {
            $deploy = $deployer->deploy(expectedSha: $merge->mergedSha);
        } catch (\Throwable $e) {
            // SSH 연결 자체 실패 — 머지는 이미 됐으므로 운영서버 상태가 모호.
            // 수동 개입 필요. PR 은 머지된 채로 남아있음.
            $this->terminate($job, AiFixJob::STATUS_DEPLOY_FAILED,
                "SSH deploy threw (merge already done): " . $e->getMessage(), $notifier,
                extra: ['merge' => $merge->toArray()],
            );
            return;
        }

        // ── 3) 결과에 따른 상태 전이 ─────────────────────────────────────────
        $testResult = $job->test_result ?? [];
        $testResult['merge']  = $merge->toArray();
        $testResult['deploy'] = $deploy->toArray();

        if ($deploy->success) {
            $job->transitionTo(AiFixJob::STATUS_DEPLOYED, [
                'deployed_commit' => $merge->mergedSha,
                'deployed_at'     => now(),
                'test_result'     => $testResult,
            ]);
        } elseif ($deploy->rolledBack) {
            $job->transitionTo(AiFixJob::STATUS_ROLLED_BACK, [
                'error_message' => mb_substr($deploy->summary(), 0, 500),
                'test_result'   => $testResult,
            ]);
        } else {
            $job->transitionTo(AiFixJob::STATUS_DEPLOY_FAILED, [
                'error_message' => mb_substr($deploy->summary(), 0, 500),
                'test_result'   => $testResult,
            ]);
        }

        $notifier->notify($job);

        // ── 4) 워크트리 정리 (성공·실패 모두) ────────────────────────────────
        try {
            $worktrees->remove($job->id);
        } catch (\Throwable $e) {
            Log::warning("[DeployAiFixJob] worktree cleanup failed for job #{$job->id}: " . $e->getMessage());
            // cleanup 실패는 배포 결과에 영향 주지 않음.
        }
    }

    /**
     * 실패 분기에서 공통으로 사용하는 종료 처리.
     * status 가 DEPLOYING 이라면 target 상태(보통 DEPLOY_FAILED)로 전이.
     */
    private function terminate(
        AiFixJob $job,
        string $target,
        string $reason,
        AiFixNotifier $notifier,
        array $extra = [],
    ): void {
        Log::warning("[DeployAiFixJob] job #{$job->id} → $target: $reason");

        try {
            $testResult = $job->test_result ?? [];
            foreach ($extra as $k => $v) {
                $testResult[$k] = $v;
            }
            $job->transitionTo($target, [
                'error_message' => mb_substr($reason, 0, 500),
                'test_result'   => $testResult,
            ]);
            $notifier->notify($job);
        } catch (\Throwable $e) {
            Log::error("[DeployAiFixJob] transition to $target failed: " . $e->getMessage());
        }
    }
}
