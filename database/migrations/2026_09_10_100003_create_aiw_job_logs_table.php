<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Works — 실시간 진행 로그(툴 호출·시스템 이벤트).
 *
 * seq 는 데몬이 부여한다. unique(job_id, seq) 로 재전송 중복을 흡수한다
 * (insertOrIgnore). raw 는 4KB 초과 시 잘라 저장하고, 전문은 데몬 로컬
 * logs/job-{id}.jsonl 에만 남긴다 — 서버 DB 비대를 막기 위함.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_job_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('aiw_jobs')->cascadeOnDelete();
            $table->unsignedInteger('seq');
            $table->enum('type', [
                'system', 'tool_use', 'tool_result', 'result', 'error', 'daemon', 'handover',
            ]);
            $table->text('content');
            $table->json('raw')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['job_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_job_logs');
    }
};
