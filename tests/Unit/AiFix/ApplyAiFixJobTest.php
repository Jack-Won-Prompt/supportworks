<?php

namespace Tests\Unit\AiFix;

use App\Jobs\ApplyAiFixJob;
use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Services\AiFix\AiCodeApplier;
use App\Services\AiFix\AiFixNotifier;
use App\Services\AiFix\StubCodeApplier;
use App\Services\AiFix\StubTestRunner;
use App\Services\AiFix\StubWorktreeManager;
use App\Services\AiFix\TestResult;
use App\Services\AiFix\TestRunner;
use App\Services\AiFix\WorktreeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyAiFixJobTest extends TestCase
{
    // 손으로 만든 테이블 대신 실제 스키마 위에서 돈다.
    //
    // 예전에는 setUp 에서 system_error_logs·users·ai_fix_jobs 를 직접 만들고
    // tearDown 에서 drop 했다. sqlite 로 돌던 시절의 방식인데, 지금 테스트는
    // MySQL(supportworks_test)에서 돈다. 그 결과 두 가지가 한꺼번에 깨졌다 —
    // 이미 있는 테이블을 만들려다 실패하고, tearDown 이 공용 테스트 DB 의
    // 진짜 users 테이블까지 지워 뒤따르는 다른 테스트를 무너뜨렸다.
    // 손으로 적은 컬럼이 실제 스키마와 어긋나기 시작한 것은 덤이다.
    use RefreshDatabase;

    private function makeApplyingJob(): AiFixJob
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm',
            'file' => 'f', 'line' => 1,
        ]);
        return AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_APPLYING,
            'branch_name'         => 'ai-fix/1',
            'changed_files'       => ['app/Foo.php'],
        ]);
    }

    private function nullNotifier(): AiFixNotifier
    {
        return new class extends AiFixNotifier {
            public array $notified = [];
            public function notify(AiFixJob $job): int { $this->notified[] = $job->status; return 0; }
        };
    }

    private function runWith(
        AiFixJob $job,
        ?WorktreeManager $worktrees = null,
        ?AiCodeApplier   $applier   = null,
        ?TestRunner      $runner    = null,
        ?AiFixNotifier   $notifier  = null,
    ): AiFixJob {
        $job = $job ?? $this->makeApplyingJob();
        (new ApplyAiFixJob($job->id))->handle(
            $worktrees ?? new StubWorktreeManager(),
            $applier   ?? new StubCodeApplier(true),
            $runner    ?? new StubTestRunner(new TestResult(passed: true, testsRun: 1, coverageDelta: 3)),
            $notifier  ?? $this->nullNotifier(),
        );
        return $job->fresh();
    }

    // ── 정상 흐름 ────────────────────────────────────────────────────────────

    public function test_happy_path_apply_test_pass_then_ready_to_deploy(): void
    {
        $job   = $this->makeApplyingJob();
        $fresh = $this->runWith($job);

        $this->assertSame(AiFixJob::STATUS_READY_TO_DEPLOY, $fresh->status);
        $this->assertNotNull($fresh->worktree_path);
        $this->assertTrue($fresh->test_result['passed']);
        $this->assertSame(3, $fresh->test_result['coverage_delta']);
    }

    public function test_apply_test_fail_transitions_to_tests_failed(): void
    {
        $job = $this->makeApplyingJob();
        $fresh = $this->runWith(
            $job,
            runner: new StubTestRunner(new TestResult(
                passed: false, testsRun: 5, failures: 2, output: '...assertion failed',
            )),
        );

        $this->assertSame(AiFixJob::STATUS_TESTS_FAILED, $fresh->status);
        $this->assertTrue($fresh->isTerminal());
        $this->assertFalse($fresh->test_result['passed']);
        $this->assertSame('tests did not pass', $fresh->error_message);
    }

    // ── 실패 케이스 ──────────────────────────────────────────────────────────

    public function test_apply_returns_false_transitions_to_tests_failed(): void
    {
        $job = $this->makeApplyingJob();
        $fresh = $this->runWith(
            $job,
            applier: new StubCodeApplier(shouldSucceed: false),
        );

        $this->assertSame(AiFixJob::STATUS_TESTS_FAILED, $fresh->status);
        $this->assertSame('code apply returned false', $fresh->error_message);
    }

    public function test_worktree_create_throws_handled_as_tests_failed(): void
    {
        $job = $this->makeApplyingJob();

        $throwingWorktrees = new class implements WorktreeManager {
            public function create(int $jobId, string $branch): string { throw new \RuntimeException('disk full'); }
            public function remove(int $jobId): void {}
        };

        $fresh = $this->runWith($job, worktrees: $throwingWorktrees);

        $this->assertSame(AiFixJob::STATUS_TESTS_FAILED, $fresh->status);
        $this->assertStringContainsString('disk full', $fresh->error_message);
    }

    public function test_applier_throws_handled_as_tests_failed(): void
    {
        $job = $this->makeApplyingJob();

        $throwingApplier = new class implements AiCodeApplier {
            public function apply(AiFixJob $j, string $p): bool { throw new \RuntimeException('AI rate-limited'); }
        };

        $fresh = $this->runWith($job, applier: $throwingApplier);

        $this->assertSame(AiFixJob::STATUS_TESTS_FAILED, $fresh->status);
        $this->assertStringContainsString('AI rate-limited', $fresh->error_message);
    }

    public function test_test_runner_throws_handled_as_tests_failed(): void
    {
        $job = $this->makeApplyingJob();

        $throwingRunner = new class implements TestRunner {
            public function run(AiFixJob $j, string $p): TestResult { throw new \RuntimeException('phpunit crashed'); }
        };

        $fresh = $this->runWith($job, runner: $throwingRunner);

        $this->assertSame(AiFixJob::STATUS_TESTS_FAILED, $fresh->status);
        $this->assertStringContainsString('phpunit crashed', $fresh->error_message);
    }

    // ── 멱등성 / skip 분기 ───────────────────────────────────────────────────

    public function test_skips_when_job_not_in_applying_state(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_AWAITING_APPROVAL,  // applying 아님
            'branch_name'         => 'ai-fix/1',
        ]);

        $this->runWith($job);

        // 상태 변경 없음
        $this->assertSame(AiFixJob::STATUS_AWAITING_APPROVAL, $job->fresh()->status);
    }

    public function test_skips_when_job_does_not_exist(): void
    {
        $notifier = $this->nullNotifier();
        // 존재하지 않는 id — silently skip
        (new ApplyAiFixJob(999999))->handle(
            new StubWorktreeManager(),
            new StubCodeApplier(true),
            new StubTestRunner(new TestResult(true)),
            $notifier,
        );

        $this->assertSame([], $notifier->notified);
    }

    // ── 알림 ─────────────────────────────────────────────────────────────────

    public function test_notifier_fires_on_ready_to_deploy(): void
    {
        $job = $this->makeApplyingJob();
        $notifier = $this->nullNotifier();
        $this->runWith($job, notifier: $notifier);

        $this->assertContains(AiFixJob::STATUS_READY_TO_DEPLOY, $notifier->notified);
    }

    public function test_notifier_fires_on_tests_failed(): void
    {
        $job = $this->makeApplyingJob();
        $notifier = $this->nullNotifier();
        $this->runWith(
            $job,
            runner: new StubTestRunner(new TestResult(passed: false)),
            notifier: $notifier,
        );

        $this->assertContains(AiFixJob::STATUS_TESTS_FAILED, $notifier->notified);
    }
}