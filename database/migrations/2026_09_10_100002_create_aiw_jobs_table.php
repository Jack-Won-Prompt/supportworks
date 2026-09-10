<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** AI Works — 작업 지시. 종료 상태(completed/failed/cancelled)가 되면 불변이다. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('aiw_agents')->cascadeOnDelete();
            // 후속 지시의 원 job. 원 job 을 지워도 이력은 남긴다.
            $table->foreignId('parent_job_id')->nullable()->constrained('aiw_jobs')->nullOnDelete();

            $table->string('title');
            $table->text('instruction');
            $table->enum('mode', ['batch', 'interactive'])->default('interactive');

            $table->string('model')->nullable();               // null = 데몬 기본값
            $table->unsignedInteger('context_limit_tokens');   // 모델별 컨텍스트 한도
            $table->json('allowed_tools');
            $table->enum('permission_mode', ['acceptEdits', 'default'])->default('acceptEdits');
            $table->decimal('cost_limit_usd', 10, 4);          // 누적 비용 상한. 0 불가(모델에서 검증)
            $table->boolean('use_branch')->default(true);

            $table->enum('status', [
                'queued', 'dispatched', 'running',
                'waiting_input', 'waiting_permission', 'handover',
                'completed', 'failed', 'cancelled',
            ])->default('queued');

            // [{session_id, started_at, ended_at, reason}] — 마지막 요소가 현재 세션
            $table->json('session_chain')->nullable();
            $table->unsignedInteger('handover_count')->default(0);
            // 현재 세션의 컨텍스트 크기. 매 턴 덮어쓰기(누적 아님). 세션 교체 시 0 리셋.
            $table->unsignedInteger('context_tokens')->default(0);

            $table->text('result_summary')->nullable();
            $table->json('changed_files')->nullable();
            $table->longText('git_diff')->nullable();          // 200KB 이하만 인라인
            $table->string('git_diff_path')->nullable();       // 초과 시 storage 경로
            $table->decimal('cost_usd', 10, 4)->default(0);    // 세션 교체 포함 누적
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_jobs');
    }
};
