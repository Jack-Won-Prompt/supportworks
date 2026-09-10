<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Works — 대화 흐름(사람·Claude 텍스트만).
 *
 * seq 는 aiw_job_logs 와 별도 시퀀스다. role=handover 는 세션 교체 시 주입된
 * 인수인계 요약이며 UI 에서 구분선으로 표시된다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_job_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('aiw_jobs')->cascadeOnDelete();
            $table->unsignedInteger('seq');
            $table->enum('role', ['user', 'assistant', 'handover']);
            $table->text('content');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('session_index')->default(0);
            // role=user: 데몬이 세션에 주입 완료한 시각. null 이면 전달 대기.
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['job_id', 'seq']);
            $table->index(['job_id', 'delivered_at']);   // /inbox 폴백 조회용
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_job_messages');
    }
};
