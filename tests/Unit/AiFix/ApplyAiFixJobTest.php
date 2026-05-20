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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApplyAiFixJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('system_error_logs', function (Blueprint $t) {
            $t->id();
            $t->string('level', 16);
            $t->string('exception')->nullable();
            $t->text('message')->nullable();
            $t->string('file')->nullable();
            $t->unsignedInteger('line')->nullable();
            $t->text('trace')->nullable();
            $t->json('context')->nullable();
            $t->boolean('is_resolved')->default(false);
            $t->unsignedBigInteger('resolved_by')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ai_fix_jobs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('system_error_log_id');
            $t->string('status', 32)->default('pending');
            $t->string('decision', 16)->nullable();
            $t->json('red_signals')->nullable();
            $t->json('yellow_signals')->nullable();
            $t->text('decision_reason')->nullable();
            $t->string('blocked_path')->nullable();
            $t->string('branch_name')->nullable();
            $t->string('worktree_path')->nullable();
            $t->text('proposed_fix_summary')->nullable();
            $t->json('changed_files')->nullable();
            $t->json('test_result')->nullable();
            $t->string('pr_url')->nullable();
            $t->string('deployed_commit', 40)->nullable();
            $t->text('deploy_log')->nullable();
            $t->unsignedBigInteger('approved_by_admin_id')->nullable();
            $t->timestamp('escalated_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('deployed_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->text('error_message')->nullable();
            $t->unsignedInteger('retry_count')->default(0);
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_fix_jobs');
        Schema::dropIfExists('system_error_logs');
        parent::tearDown();
    }

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