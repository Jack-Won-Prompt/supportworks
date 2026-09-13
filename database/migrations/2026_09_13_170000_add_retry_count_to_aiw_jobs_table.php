<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 자동 재시도 횟수.
 *
 * 환경이 사라져 실패한 작업(담당자 PC 응답 없음·데몬 재시작)은 같은 지시를 그대로
 * 다시 보내면 된다. 다만 끝없이 되풀이하면 안 되므로, 후속 job 이 앞선 횟수를
 * 물려받아 상한에서 멈춘다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->unsignedTinyInteger('retry_count')->default(0)->after('nudge_count');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->dropColumn('retry_count');
        });
    }
};
