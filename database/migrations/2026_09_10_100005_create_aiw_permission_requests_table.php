<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Works — 툴 실행 승인 요청.
 *
 * request_key 는 데몬이 만든 UUID 다. unique 라서 같은 키로 재요청해도
 * 레코드가 늘지 않는다(idempotent) — Reverb 유실 후 재시도 대비.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_permission_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('aiw_jobs')->cascadeOnDelete();
            $table->string('request_key')->unique();
            $table->string('tool_name');
            $table->json('tool_input');
            $table->enum('status', ['pending', 'allowed', 'denied', 'expired'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('deny_reason')->nullable();   // Claude 에게 전달할 거부 사유
            $table->timestamp('created_at')->nullable();

            $table->index(['job_id', 'status']);
            $table->index(['status', 'created_at']);     // 타임아웃 만료 스케줄러용
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_permission_requests');
    }
};
