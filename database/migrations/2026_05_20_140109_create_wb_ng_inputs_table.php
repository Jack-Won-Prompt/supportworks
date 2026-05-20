<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §1.8 — wb_ng_inputs.
 *
 * NG 판정 후 미스 항목 입력. 체크리스트 자기 진화의 학습 원본.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_ng_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->foreignId('review_session_id')
                ->constrained('wb_review_sessions')->cascadeOnDelete();
            $table->unsignedInteger('review_round');
            $table->json('highlights_snapshot')->nullable();
            $table->text('command_box')->nullable();
            $table->text('miss_description')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->boolean('processed_for_learning')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'review_round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_ng_inputs');
    }
};
