<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_review_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->unsignedInteger('review_round');
            $table->foreignId('generated_html_id')->constrained('wb_generated_html')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('decision', ['ok', 'ng', 'pending'])->default('pending');
            $table->char('start_hash', 64);
            $table->char('end_hash', 64)->nullable();
            $table->boolean('integrity_passed')->nullable();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'review_round']);
            $table->index(['reviewer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_review_sessions');
    }
};
