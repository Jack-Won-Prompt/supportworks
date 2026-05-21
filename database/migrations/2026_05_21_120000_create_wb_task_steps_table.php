<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 옵션 확정 → 웍스 호출 처리 과정의 step-by-step 감사 로그.
 *
 * 한 Task 가 ai_calling → result_confirm 으로 가는 동안의 모든 처리 단계를 기록.
 * 사용자가 진행 화면에서 어떤 단계에서 시간이 걸리는지 확인 가능.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('wb_task_steps', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('task_id');
            $t->unsignedBigInteger('ai_call_log_id')->nullable();
            $t->unsignedInteger('sequence');
            $t->string('code', 64);                  // 'option_saved', 'job_queued', 'prompt_built', 'ai_attempt', 'ai_success', 'html_saved' ...
            $t->string('label', 255);                // 사용자에게 보일 라벨
            $t->enum('status', ['pending','running','success','failed','skipped'])
              ->default('pending');
            $t->json('context')->nullable();         // 부가 정보 (provider, tokens, error_msg 등)
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->timestamps();

            $t->index(['task_id', 'sequence']);
            $t->index(['task_id', 'created_at']);
            $t->foreign('task_id')->references('id')->on('wb_tasks')->cascadeOnDelete();
            $t->foreign('ai_call_log_id')->references('id')->on('wb_ai_call_logs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_task_steps');
    }
};
