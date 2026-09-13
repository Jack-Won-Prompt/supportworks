<?php

namespace Tests\Unit\AiFix;

use App\Jobs\AnalyzeSystemErrorJob;
use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Services\AiFix\AiAnalyzer;
use App\Services\AiFix\AiFixOrchestrator;
use App\Services\AiFix\AnalysisResult;
use App\Services\AiFix\EscalationEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TriggerWiringTest extends TestCase
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

    // ── Job 자체 동작 ────────────────────────────────────────────────────────

    public function test_job_calls_orchestrator(): void
    {
        $err = SystemErrorLog::create([
            'level' => 'error', 'exception' => 'X', 'message' => 'm',
            'file' => 'app/Foo.php', 'line' => 1,
        ]);

        $analysis = new AnalysisResult(
            category: 'unknown', confidence: 0.95,
            changedFiles: ['app/Http/Requests/LoginRequest.php'],
            summary: '[stub]', unsure: false,
        );
        $orchestrator = new AiFixOrchestrator(
            analyzer:  new class($analysis) implements AiAnalyzer {
                public function __construct(private AnalysisResult $r) {}
                public function analyze(SystemErrorLog $e): AnalysisResult { return $this->r; }
            },
            evaluator: EscalationEvaluator::fromConfig(),
        );

        (new AnalyzeSystemErrorJob($err->id))->handle($orchestrator);

        $this->assertSame(1, AiFixJob::where('system_error_log_id', $err->id)->count());
    }

    public function test_job_silently_skips_missing_error(): void
    {
        $analysis = new AnalysisResult('unknown', 0.9, [], '[stub]');
        $orchestrator = new AiFixOrchestrator(
            analyzer:  new class($analysis) implements AiAnalyzer {
                public function __construct(private AnalysisResult $r) {}
                public function analyze(SystemErrorLog $e): AnalysisResult { return $this->r; }
            },
            evaluator: EscalationEvaluator::fromConfig(),
        );

        // 존재하지 않는 id — 예외 없이 그냥 return
        (new AnalyzeSystemErrorJob(999999))->handle($orchestrator);
        $this->assertSame(0, AiFixJob::count());
    }

    // ── SystemErrorLog hook (auto_trigger) ───────────────────────────────────

    public function test_hook_does_not_dispatch_when_flag_off(): void
    {
        config(['ai-fix.auto_trigger' => false]);
        Queue::fake();

        SystemErrorLog::log('error', 'something broke');

        Queue::assertNothingPushed();
    }

    public function test_hook_dispatches_when_flag_on_and_level_critical(): void
    {
        config(['ai-fix.auto_trigger' => true]);
        Queue::fake();

        SystemErrorLog::log('error', 'something broke');

        Queue::assertPushed(AnalyzeSystemErrorJob::class, 1);
    }

    public function test_hook_skips_non_critical_levels_even_when_flag_on(): void
    {
        config(['ai-fix.auto_trigger' => true]);
        Queue::fake();

        SystemErrorLog::log('info',    'just info');
        SystemErrorLog::log('warning', 'just warning');

        Queue::assertNothingPushed();
    }

    public function test_hook_dispatches_for_each_critical_level(): void
    {
        config(['ai-fix.auto_trigger' => true]);
        Queue::fake();

        SystemErrorLog::log('error',     'e1');
        SystemErrorLog::log('critical',  'e2');
        SystemErrorLog::log('alert',     'e3');
        SystemErrorLog::log('emergency', 'e4');

        Queue::assertPushed(AnalyzeSystemErrorJob::class, 4);
    }

    public function test_hook_passes_correct_error_log_id(): void
    {
        config(['ai-fix.auto_trigger' => true]);
        Queue::fake();

        SystemErrorLog::log('error', 'm1');
        SystemErrorLog::log('error', 'm2');

        $ids = SystemErrorLog::pluck('id')->all();
        Queue::assertPushed(AnalyzeSystemErrorJob::class, function ($j) use ($ids) {
            return in_array($j->errorLogId, $ids, true);
        });
    }
}