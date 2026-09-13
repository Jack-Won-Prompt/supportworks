<?php

namespace Tests\Unit\AiFix;

use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFixJobTest extends TestCase
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