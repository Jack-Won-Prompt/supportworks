<?php

namespace Tests\Unit\AiFix;

use App\Jobs\AnalyzeSystemErrorJob;
use App\Models\AiFixJob;
use App\Models\SystemErrorLog;
use App\Services\AiFix\AiAnalyzer;
use App\Services\AiFix\AiFixOrchestrator;
use App\Services\AiFix\AnalysisResult;
use App\Services\AiFix\EscalationEvaluator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TriggerWiringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name'); $t->string('email')->unique();
            $t->string('password')->default('x');
            $t->string('role')->nullable();
            $t->boolean('is_guest')->default(false);
            $t->boolean('is_sr_agent')->default(false);
            $t->string('company')->nullable();
            $t->string('phone')->nullable();
            $t->string('avatar')->nullable();
            $t->string('agent_status')->nullable();
            $t->unsignedBigInteger('company_group_id')->nullable();
            $t->timestamps();
        });

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
            $t->text('proposed_fix_summary')->nullable();
            $t->json('changed_files')->nullable();
            $t->timestamp('escalated_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->unsignedInteger('retry_count')->default(0);
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_fix_jobs');
        Schema::dropIfExists('system_error_logs');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

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