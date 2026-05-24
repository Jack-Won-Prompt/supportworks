<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_git_links', function (Blueprint $table) {
            // 프로젝트별 WITHWORKS 저장소 내 경로 키워드 (단일, substring 매칭).
            // 예: medical/standard, factory/lseA, pet/standard
            // 커밋의 files_json 경로에 이 키워드가 포함된 파일만 해당 프로젝트로 귀속.
            // 어느 키워드에도 매칭되지 않는 파일은 '공통' 으로 별도 분류.
            $table->string('path_prefix', 200)->nullable()->after('repo');
        });
    }

    public function down(): void
    {
        Schema::table('project_git_links', function (Blueprint $table) {
            $table->dropColumn('path_prefix');
        });
    }
};
