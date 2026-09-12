<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 매핑이 실제로 쓸 수 있는 상태인지 기록한다.
 *
 * 지금까지는 지시를 넣어 봐야 알았다. 하루에만 세 번 같은 식으로 막혔다 —
 * 폴더가 git 저장소가 아니었고, 미정리 변경이 70건 있었고, 기본 브랜치가
 * master 로 적혀 있는데 실제로는 main 이었다. 셋 다 작업이 실패한 뒤에야
 * 드러났고, 화면에는 이유 없이 멈춘 것처럼 보였다.
 *
 * 담당자 PC 가 매핑을 받는 즉시 확인해서 여기 남기면, 지시를 넣기 전에
 * 무엇을 고쳐야 하는지 화면에서 바로 알 수 있다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_agent_projects', function (Blueprint $table) {
            // ok: 지시 가능 / 그 외: 사람이 고쳐야 하는 상태
            $table->string('setup_status', 32)->nullable()->after('last_seen_at');
            // 무엇을 고쳐야 하는지 사람 말로 적는다. 화면에 그대로 보여 준다.
            $table->text('setup_message')->nullable()->after('setup_status');
            $table->timestamp('setup_checked_at')->nullable()->after('setup_message');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_agent_projects', function (Blueprint $table) {
            $table->dropColumn(['setup_status', 'setup_message', 'setup_checked_at']);
        });
    }
};
