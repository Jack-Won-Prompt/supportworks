<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('output_id')->constrained('ai_outputs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // AgentFeedbackType enum: ok | issue | text | screenshot
            $table->string('feedback_type', 32);
            $table->text('message')->nullable();
            $table->string('screenshot_path', 500)->nullable();

            // open | analyzed | applied | dismissed
            $table->string('status', 32)->default('open');

            // AI 피드백 분석 결과 (요약/적용 범위 등) JSON
            $table->json('analysis_meta')->nullable();

            $table->timestamps();

            $table->index(['output_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedbacks');
    }
};
