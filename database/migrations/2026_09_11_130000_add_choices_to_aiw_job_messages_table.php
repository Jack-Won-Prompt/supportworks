<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 모델이 제시한 선택지.
 *
 * 지시가 애매할 때 담당자가 되묻는데, 지금은 사용자가 답을 직접 타이핑해야 한다.
 * 선택지를 따로 저장하면 화면이 버튼으로 그려 한 번 클릭으로 답할 수 있다.
 * 본문에서 파싱하지 않고 데몬이 분리해 보낸다 — 평범한 번호 목록을 버튼으로
 * 착각하지 않기 위해서다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_job_messages', function (Blueprint $table) {
            $table->json('choices')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_job_messages', function (Blueprint $table) {
            $table->dropColumn('choices');
        });
    }
};
