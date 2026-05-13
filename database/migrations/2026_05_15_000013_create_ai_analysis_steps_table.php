<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_agent_sessions')->cascadeOnDelete();

            // AI가 제안한 단계 키 (예: layout, components, navigation …)
            $table->string('step_key', 64);
            $table->string('title', 255);
            $table->text('description')->nullable();

            // pending | in_progress | done | user_input_required | failed | skipped
            $table->string('status', 32)->default('pending');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('requires_user_decision')->default(false);
            // approved | rejected | revise | null (미결정)
            $table->string('user_decision', 32)->nullable();

            $table->json('meta')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            $table->unique(['session_id', 'step_key']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_steps');
    }
};
