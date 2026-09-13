<?php

namespace Tests\Unit\AiFix;

use App\Jobs\ApplyAiFixJob;
use App\Jobs\DeployAiFixJob;
use App\Models\AdminUser;
use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Services\AiFix\AiAnalyzer;
use App\Services\AiFix\AiFixNotifier;
use App\Services\AiFix\AiFixOrchestrator;
use App\Services\AiFix\AnalysisResult;
use App\Services\AiFix\EscalationEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AiFixOrchestratorTest extends TestCase
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

    /** 테스트마다 다른 분석 결과를 강제하려고 inline 분석기를 만든다. */
    private function fakeAnalyzer(AnalysisResult $r): AiAnalyzer
    {
        return new class($r) implements AiAnalyzer {
            public function __construct(private AnalysisResult $r) {}
            public function analyze(SystemErrorLog $e): AnalysisResult { return $this->r; }
        };
    }

    private function orchestratorWith(AnalysisResult $r): AiFixOrchestrator
    {
        return new AiFixOrchestrator(
            analyzer:  $this->fakeAnalyzer($r),
            evaluator: EscalationEvaluator::fromConfig(),
        );
    }

    private function makeError(array $overrides = []): SystemErrorLog
    {
        return SystemErrorLog::create(array_merge([
            'level'     => 'error',
            'exception' => 'RuntimeException',
            'message'   => 'something broke',
            'file'      => 'app/Models/User.php',
            'line'      => 42,
        ], $overrides));
    }

    // ── BLOCK 분기 ───────────────────────────────────────────────────────────

    public function test_blocks_when_payment_file_proposed(): void
    {
        $err = $this->makeError();
        $analysis = new AnalysisResult(
            category:     'unknown',
            confidence:   0.9,
            changedFiles: ['app/Services/Payment/StripeGateway.php'],
            summary:      '[stub] proposed payment fix',
        );

        $job = $this->orchestratorWith($analysis)->analyzeError($err);

        $this->assertSame(AiFixJob::STATUS_BLOCKED, $job->status);
        $this->assertSame('block', $job->decision);
        $this->assertSame('app/Services/Payment/StripeGateway.php', $job->blocked_path);
        $this->assertNotNull($job->finished_at);
    }

    public function test_blocks_when_migration_proposed(): void
    {
        $analysis = new AnalysisResult(
            category:     'db_query',
            confidence:   0.9,
            changedFiles: ['database/migrations/2026_05_20_add_field.php'],
            summary:      '[stub] add field',
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_BLOCKED, $job->status);
    }

    // ── AUTO 분기 ───────────────────────────────────────────────────────────

    public function test_auto_approved_when_only_request_file_proposed_with_high_confidence(): void
    {
        $analysis = new AnalysisResult(
            category:     'validation',
            confidence:   0.95,
            changedFiles: ['app/Http/Requests/Auth/LoginRequest.php'],
            summary:      '[stub] add required validation',
            unsure:       false,
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_AUTO_APPROVED, $job->status);
        $this->assertSame('auto', $job->decision);
        $this->assertSame('ai-fix/' . $job->id, $job->branch_name);
        $this->assertNull($job->finished_at, 'auto_approved is NOT terminal');
        $this->assertNull($job->escalated_at);
    }

    // ── ESCALATE 분기 ───────────────────────────────────────────────────────

    public function test_escalates_when_changed_file_outside_auto_eligible(): void
    {
        $analysis = new AnalysisResult(
            category:     'null_check',
            confidence:   0.92,
            changedFiles: ['app/Services/UserService.php'],
            summary:      '[stub] add null guard',
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_AWAITING_APPROVAL, $job->status);
        $this->assertSame('escalate', $job->decision);
        $this->assertNotNull($job->escalated_at);
    }

    public function test_escalates_on_low_confidence_plus_repeated_error(): void
    {
        $err = $this->makeError(['exception' => 'TypeError', 'file' => 'app/Models/Foo.php', 'line' => 10]);
        // 같은 fingerprint 3건 추가 (이번 거 포함 총 4건)
        SystemErrorLog::create(['level' => 'error', 'exception' => 'TypeError', 'file' => 'app/Models/Foo.php', 'line' => 10]);
        SystemErrorLog::create(['level' => 'error', 'exception' => 'TypeError', 'file' => 'app/Models/Foo.php', 'line' => 10]);
        SystemErrorLog::create(['level' => 'error', 'exception' => 'TypeError', 'file' => 'app/Models/Foo.php', 'line' => 10]);

        $analysis = new AnalysisResult(
            category:     'type_mismatch',
            confidence:   0.3,                                  // yellow: low confidence
            changedFiles: ['app/Http/Requests/SomeRequest.php'],
            summary:      '[stub] cast int',
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($err);

        // 2개 yellow 신호 → escalate
        $this->assertSame(AiFixJob::STATUS_AWAITING_APPROVAL, $job->status);
        $this->assertContains('classification_confidence_low', $job->yellow_signals);
        $this->assertContains('same_error_repeated',          $job->yellow_signals);
    }

    public function test_escalates_when_security_keyword_in_changed_path(): void
    {
        $analysis = new AnalysisResult(
            category:     'validation',
            confidence:   0.95,
            changedFiles: ['app/Http/Requests/PasswordResetRequest.php'],
            summary:      '[stub] add rule',
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_AWAITING_APPROVAL, $job->status);
        $this->assertContains('security_keyword_match', $job->red_signals);
    }

    // ── 멱등성 ───────────────────────────────────────────────────────────────

    public function test_returns_existing_active_job_for_same_error(): void
    {
        $err = $this->makeError();
        $analysis = new AnalysisResult(
            category:     'unknown',
            confidence:   0.9,
            changedFiles: ['app/Services/UserService.php'],
            summary:      '[stub]',
        );
        $orchestrator = $this->orchestratorWith($analysis);

        $first  = $orchestrator->analyzeError($err);
        $second = $orchestrator->analyzeError($err);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AiFixJob::where('system_error_log_id', $err->id)->count());
    }

    public function test_creates_new_job_after_previous_terminal(): void
    {
        $err = $this->makeError();
        $analysis = new AnalysisResult(
            category:     'unknown',
            confidence:   0.9,
            changedFiles: ['app/Services/Payment/Foo.php'],  // BLOCK → terminal
            summary:      '[stub]',
        );
        $first = $this->orchestratorWith($analysis)->analyzeError($err);
        $this->assertSame(AiFixJob::STATUS_BLOCKED, $first->status);
        $this->assertTrue($first->isTerminal());

        // 두 번째 호출 → 새 job 생성 (이전이 terminal 이므로)
        $analysis2 = new AnalysisResult(
            category: 'unknown', confidence: 0.9,
            changedFiles: ['app/Http/Requests/LoginRequest.php'],
            summary: '[stub]', unsure: false,
        );
        $second = $this->orchestratorWith($analysis2)->analyzeError($err);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, AiFixJob::where('system_error_log_id', $err->id)->count());
    }

    // ── 메타 ─────────────────────────────────────────────────────────────────

    public function test_branch_name_assigned(): void
    {
        $analysis = new AnalysisResult(
            category: 'unknown', confidence: 0.9,
            changedFiles: ['app/Http/Requests/LoginRequest.php'],
            summary: '[stub]', unsure: false,
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($this->makeError());

        $this->assertMatchesRegularExpression('#^ai-fix/\d+$#', $job->branch_name);
    }

    public function test_proposed_summary_and_changed_files_persisted(): void
    {
        $analysis = new AnalysisResult(
            category:     'null_check',
            confidence:   0.95,
            changedFiles: ['app/Http/Requests/A.php', 'app/Http/Requests/B.php'],
            summary:      '[stub] guard against null',
            unsure:       false,
        );
        $job = $this->orchestratorWith($analysis)->analyzeError($this->makeError());

        $this->assertSame('[stub] guard against null', $job->proposed_fix_summary);
        $this->assertSame(['app/Http/Requests/A.php', 'app/Http/Requests/B.php'], $job->changed_files);
    }

    // ── 알림 hook (notifier 가 호출되는지) ───────────────────────────────────

    /** notify() 호출을 기록만 하고 실제 FCM 은 안 보내는 스파이 */
    private function spyNotifier(): AiFixNotifier
    {
        return new class extends AiFixNotifier {
            public array $notified = [];
            public function notify(\App\Models\AiFixJob $job): int
            {
                $this->notified[] = ['id' => $job->id, 'status' => $job->status];
                return 1;
            }
        };
    }

    private function orchestratorWithNotifier(AnalysisResult $r, AiFixNotifier $n): AiFixOrchestrator
    {
        return new AiFixOrchestrator(
            analyzer:  $this->fakeAnalyzer($r),
            evaluator: EscalationEvaluator::fromConfig(),
            notifier:  $n,
        );
    }

    public function test_notifier_called_on_escalate(): void
    {
        $spy = $this->spyNotifier();
        $analysis = new AnalysisResult(
            category: 'null_check', confidence: 0.92,
            changedFiles: ['app/Services/UserService.php'],
            summary: '[stub]',
        );
        $job = $this->orchestratorWithNotifier($analysis, $spy)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_AWAITING_APPROVAL, $job->status);
        $this->assertCount(1, $spy->notified);
        $this->assertSame($job->id, $spy->notified[0]['id']);
        $this->assertSame(AiFixJob::STATUS_AWAITING_APPROVAL, $spy->notified[0]['status']);
    }

    public function test_notifier_called_on_block(): void
    {
        $spy = $this->spyNotifier();
        $analysis = new AnalysisResult(
            category: 'unknown', confidence: 0.9,
            changedFiles: ['app/Services/Payment/Foo.php'],
            summary: '[stub]',
        );
        $job = $this->orchestratorWithNotifier($analysis, $spy)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_BLOCKED, $job->status);
        $this->assertCount(1, $spy->notified);
    }

    public function test_notifier_called_on_auto_approved_but_real_notifier_would_short_circuit(): void
    {
        // orchestrator 는 항상 notifier->notify() 를 호출. 정책 필터는 notifier 안에서.
        // (실제 AiFixNotifier 의 shouldNotify(auto_approved)=false 이므로 0 반환)
        $spy = $this->spyNotifier();
        $analysis = new AnalysisResult(
            category: 'unknown', confidence: 0.95,
            changedFiles: ['app/Http/Requests/LoginRequest.php'],
            summary: '[stub]', unsure: false,
        );
        $job = $this->orchestratorWithNotifier($analysis, $spy)->analyzeError($this->makeError());

        $this->assertSame(AiFixJob::STATUS_AUTO_APPROVED, $job->status);
        $this->assertCount(1, $spy->notified);
    }

    public function test_notifier_not_called_for_existing_active_job(): void
    {
        $spy = $this->spyNotifier();
        $err = $this->makeError();
        $analysis = new AnalysisResult(
            category: 'unknown', confidence: 0.9,
            changedFiles: ['app/Services/UserService.php'],
            summary: '[stub]',
        );
        $orch = $this->orchestratorWithNotifier($analysis, $spy);

        $orch->analyzeError($err);
        $orch->analyzeError($err);

        $this->assertCount(1, $spy->notified, 'idempotent branch should skip notify on 2nd call');
    }

    // ── approve / reject ─────────────────────────────────────────────────────

    private function rawOrchestrator(?AiFixNotifier $n = null): AiFixOrchestrator
    {
        // analyzer 는 이 테스트에서 호출되지 않음 — null 대신 dummy
        $analysis = new AnalysisResult('unknown', 0.9, [], '[dummy]');
        return new AiFixOrchestrator(
            analyzer:  $this->fakeAnalyzer($analysis),
            evaluator: EscalationEvaluator::fromConfig(),
            notifier:  $n,
        );
    }

    private function makeJob(string $status): AiFixJob
    {
        $err = $this->makeError();
        return AiFixJob::create([
            'system_error_log_id' => $err->id,
            'status'              => $status,
        ]);
    }

    /**
     * 승인자. approve/reject 는 이제 id 가 아니라 모델을 받는다
     * (approved_by_id + approved_by_type 를 함께 채우기 위해서다).
     * 저장할 필요는 없다 — 오케스트레이터가 쓰는 것은 키와 클래스뿐이다.
     */
    private function admin(int $id): AdminUser
    {
        return (new AdminUser)->forceFill(['id' => $id]);
    }

    public function test_approve_from_awaiting_approval_transitions_to_applying(): void
    {
        // Bus::fake 로 후속 ApplyAiFixJob 인라인 실행을 막아 approve() 단독 책임만 검증.
        Bus::fake([ApplyAiFixJob::class]);

        $job = $this->makeJob(AiFixJob::STATUS_AWAITING_APPROVAL);
        $this->rawOrchestrator()->approve($job, $this->admin(42));

        $fresh = $job->fresh();
        $this->assertSame(AiFixJob::STATUS_APPLYING, $fresh->status);
        $this->assertSame(42, $fresh->approved_by_admin_id);
        // 승인자는 이제 다형 컬럼에도 남는다. admin_id 는 하위호환으로 함께 채운다.
        $this->assertSame(42, (int) $fresh->approved_by_id);
        $this->assertSame(AdminUser::class, $fresh->approved_by_type);
        $this->assertNotNull($fresh->approved_at);
        Bus::assertDispatched(ApplyAiFixJob::class,
            fn ($j) => $j->aiFixJobId === $job->id);
    }

    public function test_approve_from_ready_to_deploy_transitions_to_deploying(): void
    {
        // Bus::fake 로 후속 DeployAiFixJob 인라인 실행 차단.
        Bus::fake([DeployAiFixJob::class]);

        $job = $this->makeJob(AiFixJob::STATUS_READY_TO_DEPLOY);
        $this->rawOrchestrator()->approve($job, $this->admin(7));

        $this->assertSame(AiFixJob::STATUS_DEPLOYING, $job->fresh()->status);
        Bus::assertDispatched(DeployAiFixJob::class,
            fn ($j) => $j->aiFixJobId === $job->id);
    }

    public function test_approve_from_wrong_status_throws(): void
    {
        $job = $this->makeJob(AiFixJob::STATUS_PENDING);
        $this->expectException(\DomainException::class);
        $this->rawOrchestrator()->approve($job, $this->admin(1));
    }

    public function test_reject_from_awaiting_approval_transitions_to_rejected(): void
    {
        $job = $this->makeJob(AiFixJob::STATUS_AWAITING_APPROVAL);
        $this->rawOrchestrator()->reject($job, $this->admin(7), reason: '신뢰도 낮음');

        $fresh = $job->fresh();
        $this->assertSame(AiFixJob::STATUS_REJECTED, $fresh->status);
        $this->assertTrue($fresh->isTerminal());
        $this->assertSame('신뢰도 낮음', $fresh->error_message);
        $this->assertSame(7, $fresh->approved_by_admin_id);
        $this->assertNotNull($fresh->finished_at);
    }

    public function test_reject_from_ready_to_deploy_allowed(): void
    {
        $job = $this->makeJob(AiFixJob::STATUS_READY_TO_DEPLOY);
        $this->rawOrchestrator()->reject($job, $this->admin(1));

        $this->assertSame(AiFixJob::STATUS_REJECTED, $job->fresh()->status);
    }

    public function test_reject_from_wrong_status_throws(): void
    {
        $job = $this->makeJob(AiFixJob::STATUS_APPLYING);
        $this->expectException(\DomainException::class);
        $this->rawOrchestrator()->reject($job, $this->admin(1));
    }

    public function test_approve_fires_notify_hook(): void
    {
        $spy = $this->spyNotifier();
        $job = $this->makeJob(AiFixJob::STATUS_AWAITING_APPROVAL);

        $this->rawOrchestrator($spy)->approve($job, $this->admin(1));

        $this->assertCount(1, $spy->notified);
        $this->assertSame(AiFixJob::STATUS_APPLYING, $spy->notified[0]['status']);
    }

    public function test_reject_fires_notify_hook(): void
    {
        $spy = $this->spyNotifier();
        $job = $this->makeJob(AiFixJob::STATUS_AWAITING_APPROVAL);

        $this->rawOrchestrator($spy)->reject($job, $this->admin(1));

        $this->assertCount(1, $spy->notified);
        $this->assertSame(AiFixJob::STATUS_REJECTED, $spy->notified[0]['status']);
    }
}