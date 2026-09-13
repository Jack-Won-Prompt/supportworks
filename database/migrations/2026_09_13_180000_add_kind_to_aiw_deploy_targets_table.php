<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 등록 명령의 종류. 배포냐, 운영 점검·조치냐.
 *
 * 지금까지 등록할 수 있는 것은 배포 하나뿐이었다. 그래서 "서버가 이상하다" 는
 * 상황에서 할 수 있는 일이 없었다 — 사람이 SSH 로 들어가야 했고, 원격에 사람이
 * 없다는 전제가 거기서 깨졌다.
 *
 * 같은 표를 쓰는 이유는 실행 경로가 이미 둘(서버 직접 / 담당자 PC)로 갈려 있고,
 * 기록·타임아웃·권한 검사가 모두 같기 때문이다. 다른 것은 **무엇을 위한 명령인가**
 * 뿐이라 열 하나로 나눈다.
 *
 * 안전장치는 그대로다: 명령 문자열은 관리자가 등록한 행에서만 오고, 요청에서
 * 오지 않는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_deploy_targets', function (Blueprint $table) {
            $table->string('kind', 20)->default('deploy')->after('name');
            $table->index(['project_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('aiw_deploy_targets', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'kind']);
            $table->dropColumn('kind');
        });
    }
};
