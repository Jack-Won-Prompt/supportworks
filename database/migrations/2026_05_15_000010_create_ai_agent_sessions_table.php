<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title', 255);
            // Output 유형은 프로젝트 frontend_stack을 따르지만 세션에 사본 보관
            $table->enum('output_type', ['html', 'react', 'vue', 'blade']);

            // AgentSessionStatus enum (15개 값) — string으로 받고 모델에서 cast
            $table->string('status', 32)->default('draft');
            // AgentSessionStep enum (12개 값)
            $table->string('current_step', 48)->default('project_selected');

            // 사용자 선택 provider (anthropic|openai|auto)
            $table->string('ai_provider', 16)->default('auto');

            // 마지막 활동 / 일시 정지 / 실패 사유
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_sessions');
    }
};
