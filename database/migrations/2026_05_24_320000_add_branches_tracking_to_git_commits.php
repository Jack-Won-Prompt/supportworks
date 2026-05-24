<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            // 같은 sha 가 도달한 모든 브랜치명 (발견 순서대로 push).
            // 예: ["origin/feature/login", "origin/develop", "origin/main"]
            //  - [0]   = 최초 발견 브랜치 (= 일반적으로 커밋이 처음 만들어진 브랜치)
            //  - [end] = 가장 최근 sync 에서 새로 발견된 브랜치 (= 가장 늦게 머지된 브랜치)
            $table->json('branches')->nullable()->after('branch');
        });

        // 기존 행 백필 — branch 컬럼 단일값을 branches 배열의 첫 원소로
        DB::statement("UPDATE git_commits SET branches = JSON_ARRAY(branch) WHERE branches IS NULL AND branch IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            $table->dropColumn('branches');
        });
    }
};
