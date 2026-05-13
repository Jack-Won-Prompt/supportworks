<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_histories', function (Blueprint $table) {
            $table->string('history_id', 40)->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id', 40)->nullable();
            $table->enum('mode', ['general', 'project']);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('task_type', 40);
            $table->text('original_input');
            $table->json('clarification_rounds');
            $table->longText('refined_prompt');
            $table->json('metadata');
            $table->string('llm_model', 60)->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('elapsed_ms')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('project_id');
            $table->index('task_id');
            $table->index(['task_id', 'created_at']);
            $table->index('task_type');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_histories');
    }
};
