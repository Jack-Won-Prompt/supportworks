<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'review', 'approved', 'rejected', 'failed'])->default('pending');
            $table->text('input_text')->nullable();
            $table->enum('llm_provider', ['anthropic', 'openai'])->default('anthropic');
            $table->string('llm_model')->default('claude-sonnet-4-6');
            $table->string('system_prompt_version')->default('v1.0');
            $table->json('ai_raw_output')->nullable();
            $table->json('ai_structured_output')->nullable();
            $table->integer('token_input')->nullable();
            $table->integer('token_output')->nullable();
            $table->decimal('cost_estimated', 10, 4)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'created_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_sessions');
    }
};
