<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 지시·메시지에 붙는 이미지.
 *
 * 화면 관련 지시("이 부분을 이렇게 바꿔 주세요")는 말보다 그림이 정확하다.
 * 파일은 비공개 디스크에 두고, 담당자는 인증된 엔드포인트로 내려받아
 * 메모리에서 base64 로 만들어 프롬프트에 넣는다 — 작업 폴더에는 쓰지 않는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_job_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('aiw_jobs')->cascadeOnDelete();
            // 어느 발언에 붙었는가. 최초 지시문도 seq 0 메시지다.
            $table->foreignId('message_id')->constrained('aiw_job_messages')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('mime', 40);
            $table->unsignedInteger('bytes');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('original_name', 255);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['job_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_job_attachments');
    }
};
