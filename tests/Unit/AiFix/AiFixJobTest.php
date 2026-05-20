<?php

namespace Tests\Unit\AiFix;

use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiFixJobTest extends TestCase
{
    // RefreshDatabase 를 쓰지 않는다 — 전체 마이그레이션에 sqlite 비호환 migration 이 섞여 있어
    // 부팅 자체가 실패한다. 우리에게 필요한 두 테이블만 수동 생성/정리.
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

        Schema::create('admin_users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
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
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('system_error_logs');
        parent::tearDown();
    }

    private function newJob(string $status = AiFixJob::STATUS_PENDING): AiFixJob
    {
        $err = SystemErrorLog::create([
            'level'     => 'error',
            'exception' => 'TestException',
            'message'   => 'test',
            'file'      => 'test.php',
            'line'      => 1,
        ]);
        return AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => $status,
        ]);
    }

    public function test_create_defaults_to_pending(): void
    {
        $job = $this->newJob();
        $this->assertSame(AiFixJob::STATUS_PENDING, $job->status);
        $this->assertFalse($job->isTerminal());
        $this->assertNull($job->finished_at);
    }

    // ── 정상 전이 ────────────────────────────────────────────────────────────

    public function test_pending_to_analyzing(): void
    {
        $job = $this->newJob();
        $job->transitionTo(AiFixJob::STATUS_ANALYZING);
        $this->assertSame(AiFixJob::STATUS_ANALYZING, $job->fresh()->status);
    }

    public function test_full_happy_path_to_deployed(): void
    {
        $job = $this->newJob();
        $job->transitionTo(AiFixJob::STATUS_ANALYZING);
        $job->transitionTo(AiFixJob::STATUS_AUTO_APPROVED);
        $job->transitionTo(AiFixJob::STATUS_APPLYING);
        $job->transitionTo(AiFixJob::STATUS_TESTING);
        $job->transitionTo(AiFixJob::STATUS_READY_TO_DEPLOY);
        $job->transitionTo(AiFixJob::STATUS_DEPLOYING);
        $job->transitionTo(AiFixJob::STATUS_DEPLOYED, ['deployed_commit' => 'abc123def456']);

        $fresh = $job->fresh();
        $this->assertSame(AiFixJob::STATUS_DEPLOYED, $fresh->status);
        $this->assertSame('abc123def456', $fresh->deployed_commit);
        $this->assertTrue($fresh->isTerminal());
        $this->assertNotNull($fresh->finished_at);
    }

    public function test_escalate_branch_through_approval(): void
    {
        $job = $this->newJob();
        $job->transitionTo(AiFixJob::STATUS_ANALYZING);
        $job->transitionTo(AiFixJob::STATUS_AWAITING_APPROVAL, [
            'decision'        => 'escalate',
            'yellow_signals'  => ['tests_pass_but_no_coverage_delta', 'ai_self_unsure'],
            'decision_reason' => 'multiple yellow signals',
            'escalated_at'    => now(),
        ]);
        $job->transitionTo(AiFixJob::STATUS_APPLYING, ['approved_at' => now(), 'approved_by_admin_id' => null]);

        $this->assertSame(AiFixJob::STATUS_APPLYING, $job->fresh()->status);
    }

    public function test_block_decision_is_terminal(): void
    {
        $job = $this->newJob();
        $job->transitionTo(AiFixJob::STATUS_ANALYZING);
        $job->transitionTo(AiFixJob::STATUS_BLOCKED, [
            'decision'     => 'block',
            'blocked_path' => 'app/Services/Payment/Stripe.php',
        ]);

        $fresh = $job->fresh();
        $this->assertTrue($fresh->isTerminal());
        $this->assertNotNull($fresh->finished_at);
    }

    public function test_deploy_failed_to_rolled_back(): void
    {
        $job = $this->newJob(AiFixJob::STATUS_DEPLOYING);
        $job->transitionTo(AiFixJob::STATUS_ROLLED_BACK, ['error_message' => 'healthz failed']);

        $this->assertTrue($job->fresh()->isTerminal());
    }

    // ── 비정상 전이 ──────────────────────────────────────────────────────────

    public function test_skip_states_throws(): void
    {
        $job = $this->newJob();
        $this->expectException(\DomainException::class);
        $job->transitionTo(AiFixJob::STATUS_DEPLOYED);   // pending → deployed 불허
    }

    public function test_transition_out_of_terminal_throws(): void
    {
        $job = $this->newJob(AiFixJob::STATUS_DEPLOYED);
        $this->expectException(\DomainException::class);
        $job->transitionTo(AiFixJob::STATUS_PENDING);
    }

    public function test_reject_from_awaiting_approval(): void
    {
        $job = $this->newJob(AiFixJob::STATUS_AWAITING_APPROVAL);
        $job->transitionTo(AiFixJob::STATUS_REJECTED);
        $this->assertTrue($job->fresh()->isTerminal());
    }

    // ── 스코프 ──────────────────────────────────────────────────────────────

    public function test_awaiting_approval_scope(): void
    {
        $this->newJob();                                                   // pending
        $awaiting = $this->newJob(AiFixJob::STATUS_AWAITING_APPROVAL);     // 매칭
        $this->newJob(AiFixJob::STATUS_DEPLOYED);                          // terminal

        $ids = AiFixJob::awaitingApproval()->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$awaiting->id], $ids);
    }

    public function test_active_scope_excludes_terminal(): void
    {
        $pending  = $this->newJob();
        $applying = $this->newJob(AiFixJob::STATUS_APPLYING);
        $deployed = $this->newJob(AiFixJob::STATUS_DEPLOYED);

        $ids = AiFixJob::active()->pluck('id')->all();
        $this->assertContains($pending->id,  $ids);
        $this->assertContains($applying->id, $ids);
        $this->assertNotContains($deployed->id, $ids);
    }

    // ── 캐스팅 ──────────────────────────────────────────────────────────────

    public function test_json_columns_cast_correctly(): void
    {
        $job = $this->newJob();
        $job->update([
            'red_signals'     => ['many_files_changed'],
            'yellow_signals'  => ['classification_confidence_low', 'ai_self_unsure'],
            'changed_files'   => ['app/Models/User.php', 'app/Http/Controllers/UserController.php'],
            'test_result'     => ['passed' => true, 'coverage_delta' => 12],
        ]);

        $fresh = $job->fresh();
        $this->assertIsArray($fresh->red_signals);
        $this->assertSame(['many_files_changed'], $fresh->red_signals);
        $this->assertSame(['passed' => true, 'coverage_delta' => 12], $fresh->test_result);
    }

    // ── 관계 ────────────────────────────────────────────────────────────────

    public function test_belongs_to_system_error_log(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm',
            'file'  => 'f.php',  'line'      => 1,
        ]);
        $job = AiFixJob::create(['system_error_log_id' => $err->id]);

        $this->assertSame($err->id, $job->systemErrorLog->id);
    }
}