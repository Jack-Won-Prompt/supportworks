<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.4 — wb_ai_call_logs.
 *
 * AI 호출 1회마다 1 row. Claude(primary) → OpenAI(fallback) 결과를 모두 기록.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_ai_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->enum('stage', ['spec_generation', 'html_generation', 'regeneration']);
            $table->unsignedInteger('review_round')->nullable();
            $table->foreignId('internal_prompt_id')->nullable()
                ->constrained('wb_internal_prompts')->nullOnDelete();

            $table->enum('primary_provider', ['claude'])->default('claude');
            $table->boolean('fallback_used')->default(false);
            $table->enum('final_provider', ['claude', 'openai', 'none'])->default('none');

            $table->enum('status', ['success', 'failed', 'cancelled'])->default('success');

            $table->enum('primary_attempt_status', [
                'success', 'timeout', 'rate_limit', 'content_filter',
                'http_5xx', 'http_4xx', 'parse_error', 'cancelled', 'other',
            ])->default('success');
            $table->text('primary_error_message')->nullable();

            $table->enum('fallback_attempt_status', [
                'success', 'timeout', 'rate_limit', 'content_filter',
                'http_5xx', 'http_4xx', 'parse_error', 'cancelled', 'other',
            ])->nullable();
            $table->text('fallback_error_message')->nullable();

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 10, 4)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();

            $table->foreignId('generated_html_id')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
            $table->index(['final_provider', 'fallback_used']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_ai_call_logs');
    }
};
