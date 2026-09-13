<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 이 오류를 자동으로 고쳐도 되는가에 대한 판정.
 *
 * 판정과 실행을 나눠 둔다. 먼저 판정만 붙여 화면에 보여 주고, 며칠 보고 나서
 * 작업 지시 생성을 켠다. 한 번에 켜면 잘못 판정된 오류가 첫날 밤에 그대로
 * 지시로 나가고, 그때는 이미 운영에 무언가 올라간 뒤다.
 *
 * 이유(verdict_reason)를 함께 남긴다. 판정만 있고 이유가 없으면, 사람이 그
 * 판정을 의심할 때 확인할 방법이 코드를 읽는 것밖에 없다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_error_reports', function (Blueprint $table) {
            // auto  : 자동 수정 대상
            // human : 사람이 봐야 한다(결제·인증·마이그레이션 등)
            // ignore: 고칠 대상이 아니다(404·봇 스캔·클라이언트 유발)
            $table->string('verdict', 16)->nullable()->after('status');
            $table->string('verdict_reason', 255)->nullable()->after('verdict');

            $table->index(['project_id', 'verdict']);
        });
    }

    public function down(): void
    {
        Schema::table('aiw_error_reports', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'verdict']);
            $table->dropColumn(['verdict', 'verdict_reason']);
        });
    }
};
