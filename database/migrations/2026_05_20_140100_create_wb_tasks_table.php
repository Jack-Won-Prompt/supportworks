<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.2 — wb_tasks.
 *
 * Task = 화면 1건 단위. 완료 Task는 불변, 재실행/복제 시 parent_task_id로 분기.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // 모드 B 입력 산출물 참조 (PlanningDoc/ProjectFile 등)
            $table->string('spec_reference_type', 64)->nullable();
            $table->unsignedBigInteger('spec_reference_id')->nullable();

            $table->enum('mode', ['new', 'enhance']);

            // v11: 재실행/복제 분기
            $table->foreignId('parent_task_id')->nullable()
                ->constrained('wb_tasks')->nullOnDelete();
            $table->enum('reopen_reason', ['reopen', 'clone'])->nullable();

            $table->foreignId('assignee_id')->constrained('users')->cascadeOnDelete();

            $table->string('current_stage', 32)->default('draft');
            $table->enum('status', [
                'draft', 'in_progress', 'ai_calling', 'review',
                'completed', 'cancelled',
            ])->default('draft');

            $table->unsignedInteger('current_review_round')->default(0);
            $table->string('output_type', 16)->default('html');

            // 누적 통계 (성공 호출만 가산)
            $table->unsignedInteger('total_ai_calls')->default(0);
            $table->unsignedBigInteger('total_tokens_used')->default(0);
            $table->decimal('total_cost_usd', 10, 4)->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['parent_task_id']);
            $table->index(['assignee_id', 'status']);
            $table->index(['spec_reference_type', 'spec_reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_tasks');
    }
};
