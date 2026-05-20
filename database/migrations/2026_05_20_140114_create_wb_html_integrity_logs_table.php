<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §1.6 — wb_html_integrity_logs.
 *
 * 검수 시작/종료 시 SHA-256 비교 결과.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_html_integrity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_session_id')
                ->constrained('wb_review_sessions')->cascadeOnDelete();
            $table->foreignId('generated_html_id')
                ->constrained('wb_generated_html')->cascadeOnDelete();
            $table->char('start_hash', 64);
            $table->char('end_hash', 64);
            $table->boolean('passed');
            $table->text('failure_reason')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('review_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_html_integrity_logs');
    }
};
