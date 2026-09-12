<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 온라인 판정을 담당자 단위에서 매핑(프로젝트) 단위로 옮긴다.
 *
 * 한 PC 가 프로젝트마다 따로 프로세스를 띄우면, 그중 하나만 죽는 일이 생긴다.
 * 담당자 하나의 last_seen_at 으로는 그걸 구분할 수 없어 나머지 프로젝트까지
 * 온라인으로 보이거나(실제로는 멈춤) 그 반대가 된다.
 *
 * 프로젝트마다 도는 프로세스가 자기 매핑에 하트비트를 남기면, 화면은 어느
 * 프로젝트가 살아 있는지 정확히 말할 수 있다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_agent_projects', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('default_branch');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_agent_projects', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
