<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('artifact_id')->nullable()->constrained('ai_agent_artifacts')->nullOnDelete();
            $table->string('stage', 50)->nullable();
            $table->string('task_type', 100)->nullable();
            $table->string('model', 100);               // claude-sonnet-4-5 등
            $table->string('provider', 50)->default('anthropic'); // anthropic / openai
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_usage_logs');
    }
};
