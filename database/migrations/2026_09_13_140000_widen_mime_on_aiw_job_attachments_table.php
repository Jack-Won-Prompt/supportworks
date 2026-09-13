<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * mime 컬럼을 넓힌다.
 *
 * 이미지만 받던 시절에는 40자로 충분했다(image/png 는 9자). 문서 첨부를 받기
 * 시작하면서 Office MIME 이 들어오는데 이게 65자다:
 *   application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
 *
 * 넓히지 않으면 xlsx·docx·pptx 첨부가 "Data too long for column 'mime'" 로
 * 500 이 된다 — 실제로 그렇게 터졌다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_job_attachments', function (Blueprint $table) {
            $table->string('mime', 120)->change();
        });
    }

    public function down(): void
    {
        // 되돌리기 전에 긴 값이 남아 있으면 잘린다. 문서 첨부를 먼저 지운다.
        Schema::table('aiw_job_attachments', function (Blueprint $table) {
            $table->string('mime', 40)->change();
        });
    }
};
