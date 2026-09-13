<?php

namespace Tests\Unit\AiFix;

use App\Jobs\DeployAiFixJob;
use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Services\AiFix\AiFixNotifier;
use App\Services\AiFix\DeployResult;
use App\Services\AiFix\GitHubMerger;
use App\Services\AiFix\MergeResult;
use App\Services\AiFix\RemoteDeployer;
use App\Services\AiFix\StubGitHubMerger;
use App\Services\AiFix\StubRemoteDeployer;
use App\Services\AiFix\StubWorktreeManager;
use App\Services\AiFix\WorktreeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployAiFixJobTest extends TestCase
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
            public function mergeBranch(string $branch, string $target, string $commitTitle, string $commitBody = '', ?string $worktreePath = null): MergeResult {
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
