<?php

namespace App\Jobs;

use App\Models\AiFixJob;
use App\Services\AiFix\AiCodeApplier;
use App\Services\AiFix\AiFixNotifier;
use App\Services\AiFix\TestRunner;
use App\Services\AiFix\WorktreeManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 관리자가 승인한(applying) AiFixJob 을 받아 worktree 생성 → 코드 적용 → 테스트 →
 * ready_to_deploy / tests_failed 로 상태 전이까지 진행하는 큐 잡.
 *
 * 흐름:
 *   APPLYING (already)
 *     ├─ WorktreeManager::create() → worktree_path 저장
 *     ├─ AiCodeApplier::apply()    → 실패 시 tests_failed (with error_message)
 *     └─ transitionTo(TESTING)
 *   TESTING
 *     ├─ TestRunner::run()
 *     └─ result.passed ? READY_TO_DEPLOY : TESTS_FAILED
 *
 *   터미널 상태에서 notifier 가 자동 발송 (정책에 따라).
 *
 * 멱등성: 이미 APPLYING/TESTING 이 아닌 job 은 그냥 skip.
 */
class ApplyAiFixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;       // 자동 재시도 안 함 (멱등성 + 워크트리 충돌 방지)

    public function __construct(public readonly int $aiFixJobId) {}

    public function handle(
        WorktreeManager $worktrees,
        AiCodeApplier   $applier,
        TestRunner      $runner,
        ?AiFixNotifier  $notifier = null,
    ): void {
        $job = AiFixJob::find($this->aiFixJobId);
        if ($job === null) {
            Log::info("[ApplyAiFixJob] job #{$this->aiFixJobId} not found");
            return;
        }
        if ($job->status !== AiFixJob::STATUS_APPLYING) {
            Log::info("[ApplyAiFixJob] job #{$job->id} not in applying state ({$job->status}) — skip");
            return;
        }

        $notifier ??= app(AiFixNotifier::class);

        // ── 1) 워크트리 생성 ──────────────────────────────────────────────────
        try {
            $path = $worktrees->create($job->id, $job->branch_name ?? "ai-fix/{$job->id}");
            $job->update(['worktree_path' => $path]);
        } catch (\Throwable $e) {
            $this->terminateAsTestsFailed($job, "worktree create failed: " . $e->getMessage(), $notifier);
            return;
        }

        // ── 2) 코드 적용 ──────────────────────────────────────────────────────
        try {
            $ok = $applier->apply($job, $path);
        } catch (\Throwable $e) {
            $this->terminateAsTestsFailed($job, "code apply failed: " . $e->getMessage(), $notifier);
            return;
        }
        if (!$ok) {
            $this->terminateAsTestsFailed($job, 'code apply returned false', $notifier);
            return;
        }

        // ── 3) testing 으로 전이 ──────────────────────────────────────────────
        $job->transitionTo(AiFixJob::STATUS_TESTING);

        // ── 4) 테스트 실행 ────────────────────────────────────────────────────
        try {
            $result = $runner->run($job, $path);
        } catch (\Throwable $e) {
            $this->terminateAsTestsFailed($job, "test run threw: " . $e->getMessage(), $notifier);
            return;
        }

        $job->test_result = $result->toArray();

        // ── 5) 결과에 따른 전이 ──────────────────────────────────────────────
        if ($result->passed) {
            $job->transitionTo(AiFixJob::STATUS_READY_TO_DEPLOY, [
                'test_result' => $result->toArray(),
            ]);
        } else {
            $job->transitionTo(AiFixJob::STATUS_TESTS_FAILED, [
                'test_result'   => $result->toArray(),
                'error_message' => 'tests did not pass',
            ]);
        }

        $notifier->notify($job);
    }

    private function terminateAsTestsFailed(AiFixJob $job, string $reason, AiFixNotifier $notifier): void
    {
        Log::warning("[ApplyAiFixJob] job #{$job->id} failed: $reason");

        // applying 에서 직접 tests_failed 로 전이 가능 (상태 머신에 정의됨)
        try {
            $job->transitionTo(AiFixJob::STATUS_TESTS_FAILED, [
                'error_message' => mb_substr($reason, 0, 500),
            ]);
            $notifier->notify($job);
        } catch (\Throwable $e) {
            Log::error("[ApplyAiFixJob] transition to tests_failed failed: " . $e->getMessage());
        }
    }
}