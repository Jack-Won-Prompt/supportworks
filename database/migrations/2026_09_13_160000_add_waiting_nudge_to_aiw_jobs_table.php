<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 사람의 답을 기다리기 시작한 시각과 재알림 횟수.
 *
 * 지금은 대기에 들어갈 때 푸시를 한 번 보내고 끝이다. 그 알림을 놓치면 작업은
 * 세션 최대 수명(기본 2시간)까지 서 있다가 조용히 중단된다 — 사람은 "왜 안
 * 끝났지" 로만 알게 된다.
 *
 * updated_at 으로는 대신할 수 없다. 데몬이 매 턴 job 을 저장하므로 언제부터
 * 기다렸는지가 남지 않는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->timestamp('waiting_since')->nullable()->after('finished_at');
            $table->unsignedTinyInteger('nudge_count')->default(0)->after('waiting_since');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->dropColumn(['waiting_since', 'nudge_count']);
        });
    }
};
