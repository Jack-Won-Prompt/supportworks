<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 배포 명령을 어디서 실행할지.
 *
 * 지금까지는 이 서버(큐 워커)에서만 돌았다. 그래서 운영 서버가 다른 프로젝트는
 * 배포할 수 없었다 — Unicorn Project 의 대상 폴더가 이 서버에 없어 늘 실패했다.
 *
 * agent 로 두면 그 프로젝트를 맡은 담당자 PC 가 실행한다. 담당자 PC 는 이미
 * 각 서버의 접속 키를 들고 있으므로, 이 서버가 남의 운영 서버 키를 갖지 않아도
 * 된다. 대신 그 PC 는 Claude 가 Bash 를 자동 승인으로 돌리는 곳이라는 점을
 * 알고 써야 한다(개인키 접근은 샌드박스 규칙으로 막아 두었다).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_deploy_targets', function (Blueprint $table) {
            // server: 큐 워커가 이 서버에서 실행(기존 동작) / agent: 담당자 PC 가 실행
            $table->string('runs_on', 16)->default('server')->after('command');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_deploy_targets', function (Blueprint $table) {
            $table->dropColumn('runs_on');
        });
    }
};
