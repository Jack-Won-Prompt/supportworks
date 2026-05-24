<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // project_id 를 nullable 로 변경 — SR 전용 서머리는 project 없이 생성
        Schema::table('weekly_ai_summaries', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });

        Schema::table('weekly_ai_summaries', function (Blueprint $table) {
            // SR 전용 서머리의 회사 ID 배열 (정렬 저장). 일반 프로젝트 서머리는 NULL.
            $table->json('sr_company_ids')->nullable()->after('project_id');

            // 캐시 키 — "p:{project_id}" 또는 "sr:{sorted_company_ids}"
            // 같은 scope·type·week 조합에 대해 unique 한 캐시.
            $table->string('scope_key', 200)->nullable()->after('sr_company_ids');

            $table->index(['scope_key', 'summary_type', 'week_start_date'], 'idx_summaries_scope');
        });

        // 기존 행에 scope_key 백필
        DB::statement("UPDATE weekly_ai_summaries SET scope_key = CONCAT('p:', project_id) WHERE project_id IS NOT NULL AND scope_key IS NULL");
    }

    public function down(): void
    {
        Schema::table('weekly_ai_summaries', function (Blueprint $table) {
            $table->dropIndex('idx_summaries_scope');
            $table->dropColumn(['sr_company_ids', 'scope_key']);
        });
        // project_id 를 다시 NOT NULL 로 — SR 전용 행은 down 시 사전 정리 필요
        Schema::table('weekly_ai_summaries', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
        });
    }
};
