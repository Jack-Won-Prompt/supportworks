<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 작업 지시를 쓸 수 있는 사람 표시.
 *
 * AI Works 는 지시 한 줄이 작업 PC 의 소스를 고치고 운영 서버에 배포까지 한다.
 * 그래서 한동안 시스템 관리자 전용으로 뒀는데, 실제로 지시를 내리는 사람은
 * 담당자 PC 를 맡은 실무자다(예: 이윤석). 관리자 계정을 나눠 주는 대신 이 옵션을
 * 켜 준다.
 *
 * 이 플래그만으로는 부족하다 — 해당 프로젝트의 구성원이어야 한다. 둘 다여야
 * 열리고, 관리자는 이 플래그와 무관하게 항상 열린다. 판단은 AiwJobPolicy 한 곳에
 * 있다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_aiw_operator')->default(false)->after('is_sr_agent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_aiw_operator');
        });
    }
};
