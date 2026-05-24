<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_groups', function (Blueprint $table) {
            // WITHWORKS 저장소 내 경로 키워드 (substring 매칭, 단일)
            // 예: medical/standard, factory/lseA, pet/standard
            // 커밋의 files_json 경로에 이 키워드가 포함된 파일이 이 회사 영역으로 귀속됨.
            $table->string('path_prefix', 200)->nullable()->after('uses_withworks');

            // 사용자 사이드바 'SR 관리' 메뉴 아래 이 회사 노출 여부.
            // 관리자/SR 담당자는 모든 ON 회사 노출, 일반 사용자는 자기 회사만.
            $table->boolean('shows_in_sr_menu')->default(false)->after('path_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('company_groups', function (Blueprint $table) {
            $table->dropColumn(['path_prefix', 'shows_in_sr_menu']);
        });
    }
};
