<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_sessions', function (Blueprint $table) {
            $table->string('session_id', 40)->primary();
            $table->unsignedBigInteger('user_id');
            $table->enum('mode', ['general', 'project']);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->text('original_input');
            $table->unsignedSmallInteger('current_round')->default(1);
            $table->json('rounds_data');
            $table->enum('status', ['in_progress', 'completed', 'expired', 'abandoned'])
                  ->default('in_progress');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();

            $table->index('user_id');
            $table->index(['status', 'expires_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_sessions');
    }
};
