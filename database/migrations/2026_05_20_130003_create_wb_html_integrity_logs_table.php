<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_html_integrity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_session_id')->constrained('wb_review_sessions')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->unsignedInteger('review_round');
            $table->enum('check_type', ['start', 'end', 'spot']);
            $table->char('expected_hash', 64);
            $table->char('actual_hash', 64);
            $table->boolean('passed');
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['review_session_id', 'check_type']);
            $table->index(['task_id', 'passed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_html_integrity_logs');
    }
};
