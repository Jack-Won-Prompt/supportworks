<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 비용 상한을 끌 수 있게 한다(null = 제한 없음).
 *
 * 이 PC 의 담당자는 구독 로그인으로 돈다 — 화면의 금액은 "API 로 썼다면
 * 얼마였을지"의 환산값이고 실제 청구는 없다. 그래서 상한의 목적은 비용이
 * 아니라 폭주를 막는 것뿐인데, 기본값 $2 로는 테스트 실행처럼 조금만 무거운
 * 작업도 중간에 끊겼다.
 *
 * 폭주 방어가 사라지는 것은 아니다 — 데몬의 JOB_TIMEOUT_SEC(30분)과
 * SESSION_MAX_SEC(2시간)이 시간으로 같은 일을 한다. 상한은 그 위에 얹는
 * 선택 장치로 남긴다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->decimal('cost_limit_usd', 10, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        // 되돌릴 때 null 이 남아 있으면 컬럼을 되돌릴 수 없다. 기본값으로 채운다.
        DB::table('aiw_jobs')->whereNull('cost_limit_usd')
            ->update(['cost_limit_usd' => config('aiw.default_cost_limit_usd', 2.0)]);

        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->decimal('cost_limit_usd', 10, 4)->nullable(false)->change();
        });
    }
};
