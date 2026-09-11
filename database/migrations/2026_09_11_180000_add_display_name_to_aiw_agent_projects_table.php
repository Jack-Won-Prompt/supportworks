<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 프로젝트마다 다른 담당자 이름.
 *
 * 담당자 하나(= PC 한 대)가 여러 프로젝트를 맡을 때, 프로젝트별로 실제 책임자가
 * 다를 수 있다. 데몬을 여러 개 띄우는 대신 표시 이름만 나눈다 — 프로세스와
 * 토큰을 늘리지 않으면서 화면에서는 구분된다.
 *
 * 비워 두면 담당자 본래 이름을 쓴다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_agent_projects', function (Blueprint $table) {
            $table->string('display_name', 100)->nullable()->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_agent_projects', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
