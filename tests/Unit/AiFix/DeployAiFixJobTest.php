<?php

namespace Tests\Unit\AiFix;

use App\Jobs\DeployAiFixJob;
use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Services\AiFix\AiFixNotifier;
use App\Services\AiFix\GitHubMerger;
use App\Services\AiFix\MergeResult;
use App\Services\AiFix\RemoteDeployer;
use App\Services\AiFix\DeployResult;
use App\Services\AiFix\StubGitHubMerger;
use App\Services\AiFix\StubRemoteDeployer;
use App\Services\AiFix\StubWorktreeManager;
use App\Services\AiFix\WorktreeManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeployAiFixJobTest extends TestCase
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

    private function makeDeployingJob(): AiFixJob
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm',
            'file' => 'f', 'line' => 1,
        ]);
        return AiFixJob::create([
            'system_error_log_id'  => $err->id,
            'status'               => AiFixJob::STATUS_DEPLOYING,
            'branch_name'          => 'ai-fix/1',
            'proposed_fix_summary' => 'add null guard to User::name()',
            'changed_files'        => ['app/Models/User.php'],
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
        ?GitHubMerger    $merger    = null,
        ?RemoteDeployer  $deployer  = null,
        ?WorktreeManager $worktrees = null,
        ?AiFixNotifier   $notifier  = null,
    ): AiFixJob {
        (new DeployAiFixJob($job->id))->handle(
            $merger    ?? new StubGitHubMerger(),
            $deployer  ?? new StubRemoteDeployer(exitCode: 0),
            $worktrees ?? new StubWorktreeManager(),
            $notifier  ?? $this->nullNotifier(),
        );
        return $job->fresh();
    }

    // ── 정상 흐름 ─────────────────────────────────────────────────────────────

    public function test_happy_path_merge_and_deploy_transitions_to_deployed(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith($job);

        $this->assertSame(AiFixJob::STATUS_DEPLOYED, $fresh->status);
        $this->assertNotNull($fresh->deployed_commit);
        $this->assertNotNull($fresh->deployed_at);
        $this->assertTrue($fresh->isTerminal());
        $this->assertTrue($fresh->test_result['merge']['merged']);
        $this->assertTrue($fresh->test_result['deploy']['success']);
    }

    public function test_deployed_commit_matches_merged_sha(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith($job);

        // StubGitHubMerger 가 만든 merged_sha 가 deployed_commit 에 저장돼야
        $this->assertSame(
            $fresh->test_result['merge']['merged_sha'],
            $fresh->deployed_commit,
        );
    }

    // ── 머지 실패 ─────────────────────────────────────────────────────────────

    public function test_merge_rejected_transitions_to_deploy_failed(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith(
            $job,
            merger: new StubGitHubMerger(shouldFail: true, failureReason: 'PR has conflicts'),
        );

        $this->assertSame(AiFixJob::STATUS_DEPLOY_FAILED, $fresh->status);
        $this->assertStringContainsString('PR has conflicts', $fresh->error_message);
        $this->assertNull($fresh->deployed_commit);
    }

    public function test_merger_throws_handled_as_deploy_failed(): void
    {
        $job = $this->makeDeployingJob();

        $throwing = new class implements GitHubMerger {
            public function mergeBranch(string $branch, string $target, string $commitTitle, string $commitBody = ''): MergeResult {
                throw new \RuntimeException('GitHub 502 Bad Gateway');
            }
        };

        $fresh = $this->runWith($job, merger: $throwing);

        $this->assertSame(AiFixJob::STATUS_DEPLOY_FAILED, $fresh->status);
        $this->assertStringContainsString('GitHub 502', $fresh->error_message);
    }

    // ── deploy.sh 실패 분기 ───────────────────────────────────────────────────

    public function test_deploy_exit_4_transitions_to_rolled_back(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(exitCode: 4, stdout: 'healthz failed → rolled back'),
        );

        $this->assertSame(AiFixJob::STATUS_ROLLED_BACK, $fresh->status);
        $this->assertTrue($fresh->test_result['deploy']['rolled_back']);
        $this->assertNull($fresh->deployed_commit, '롤백된 경우 deployed_commit 없음');
    }

    public function test_deploy_exit_5_transitions_to_deploy_failed_with_severe_message(): void
    {
        // exit 5 = 헬스체크 실패 + 롤백도 실패 → 수동 개입 필요
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(exitCode: 5),
        );

        $this->assertSame(AiFixJob::STATUS_DEPLOY_FAILED, $fresh->status);
        $this->assertStringContainsString('manual intervention', $fresh->error_message);
    }

    public function test_deploy_exit_1_preflight_failure(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(exitCode: 1),
        );

        $this->assertSame(AiFixJob::STATUS_DEPLOY_FAILED, $fresh->status);
        $this->assertStringContainsString('preflight', $fresh->error_message);
    }

    public function test_deploy_exit_3_migration_failure(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(exitCode: 3),
        );

        $this->assertSame(AiFixJob::STATUS_DEPLOY_FAILED, $fresh->status);
        $this->assertStringContainsString('migration', $fresh->error_message);
    }

    public function test_deployer_throws_handled_as_deploy_failed_with_merge_info_preserved(): void
    {
        $job   = $this->makeDeployingJob();
        $fresh = $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(throwOnDeploy: true, exception: 'SSH connection timeout'),
        );

        $this->assertSame(AiFixJob::STATUS_DEPLOY_FAILED, $fresh->status);
        $this->assertStringContainsString('SSH connection timeout', $fresh->error_message);
        // 머지는 이미 됐으므로 머지 정보는 test_result 에 보존돼야
        $this->assertTrue($fresh->test_result['merge']['merged']);
    }

    // ── 멱등성 / skip 분기 ───────────────────────────────────────────────────

    public function test_skips_when_job_not_in_deploying_state(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm', 'file' => 'f', 'line' => 1,
        ]);
        $job = AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => AiFixJob::STATUS_READY_TO_DEPLOY,  // deploying 아님
            'branch_name'         => 'ai-fix/1',
        ]);

        $this->runWith($job);

        $this->assertSame(AiFixJob::STATUS_READY_TO_DEPLOY, $job->fresh()->status);
    }

    public function test_skips_when_job_does_not_exist(): void
    {
        $notifier = $this->nullNotifier();
        (new DeployAiFixJob(999999))->handle(
            new StubGitHubMerger(),
            new StubRemoteDeployer(),
            new StubWorktreeManager(),
            $notifier,
        );
        $this->assertSame([], $notifier->notified);
    }

    // ── 워크트리 정리 ────────────────────────────────────────────────────────

    public function test_worktree_cleanup_called_on_success(): void
    {
        $job = $this->makeDeployingJob();

        $spyWorktrees = new class implements WorktreeManager {
            public array $removed = [];
            public function create(int $jobId, string $branch): string { return "/tmp/fix-$jobId"; }
            public function remove(int $jobId): void { $this->removed[] = $jobId; }
        };

        $this->runWith($job, worktrees: $spyWorktrees);

        $this->assertContains($job->id, $spyWorktrees->removed);
    }

    public function test_worktree_cleanup_called_on_rollback(): void
    {
        $job = $this->makeDeployingJob();

        $spyWorktrees = new class implements WorktreeManager {
            public array $removed = [];
            public function create(int $jobId, string $branch): string { return "/tmp/fix-$jobId"; }
            public function remove(int $jobId): void { $this->removed[] = $jobId; }
        };

        $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(exitCode: 4),
            worktrees: $spyWorktrees,
        );

        $this->assertContains($job->id, $spyWorktrees->removed,
            '롤백된 경우에도 워크트리는 정리돼야');
    }

    public function test_worktree_cleanup_failure_does_not_affect_status(): void
    {
        $job = $this->makeDeployingJob();

        $throwingWorktrees = new class implements WorktreeManager {
            public function create(int $jobId, string $branch): string { return "/tmp/fix-$jobId"; }
            public function remove(int $jobId): void { throw new \RuntimeException('worktree locked'); }
        };

        $fresh = $this->runWith($job, worktrees: $throwingWorktrees);

        // 정리 실패해도 배포 결과는 정상 처리돼야
        $this->assertSame(AiFixJob::STATUS_DEPLOYED, $fresh->status);
    }

    // ── 알림 ──────────────────────────────────────────────────────────────────

    public function test_notifier_fires_on_deployed(): void
    {
        $job = $this->makeDeployingJob();
        $notifier = $this->nullNotifier();
        $this->runWith($job, notifier: $notifier);

        $this->assertContains(AiFixJob::STATUS_DEPLOYED, $notifier->notified);
    }

    public function test_notifier_fires_on_rolled_back(): void
    {
        $job = $this->makeDeployingJob();
        $notifier = $this->nullNotifier();
        $this->runWith(
            $job,
            deployer: new StubRemoteDeployer(exitCode: 4),
            notifier: $notifier,
        );

        $this->assertContains(AiFixJob::STATUS_ROLLED_BACK, $notifier->notified);
    }

    public function test_notifier_fires_on_deploy_failed(): void
    {
        $job = $this->makeDeployingJob();
        $notifier = $this->nullNotifier();
        $this->runWith(
            $job,
            merger: new StubGitHubMerger(shouldFail: true),
            notifier: $notifier,
        );

        $this->assertContains(AiFixJob::STATUS_DEPLOY_FAILED, $notifier->notified);
    }
}
