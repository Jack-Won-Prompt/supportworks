<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ai_fix_jobs.approved_by_admin_id (admin_users 만 가능) 를
 * polymorphic 패턴(approved_by_id + approved_by_type) 으로 확장.
 *
 * 두 가드(웹 admin_users / 모바일 users.role=admin) 양쪽에서 승인 가능.
 *
 * - 새 컬럼 추가:
 *     approved_by_id   (nullable bigint)
 *     approved_by_type (nullable string, App\Models\AdminUser 또는 App\Models\User)
 *     index(approved_by_type, approved_by_id)
 * - 기존 데이터 이전: approved_by_admin_id 가 있던 row 는 type=AdminUser 로 채움
 * - approved_by_admin_id 컬럼은 그대로 유지 (deprecated, 다음 정리 migration 에서 제거 가능)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_fix_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by_id')->nullable()->after('approved_by_admin_id');
            $table->string('approved_by_type', 60)->nullable()->after('approved_by_id');
            $table->index(['approved_by_type', 'approved_by_id'], 'ai_fix_jobs_approved_by_morph_idx');
        });

        // 기존 admin_users 기반 row 의 polymorphic 컬럼을 backfill
        DB::table('ai_fix_jobs')
            ->whereNotNull('approved_by_admin_id')
            ->update([
                'approved_by_id'   => DB::raw('approved_by_admin_id'),
                'approved_by_type' => \App\Models\AdminUser::class,
            ]);
    }

    public function down(): void
    {
        Schema::table('ai_fix_jobs', function (Blueprint $table) {
            $table->dropIndex('ai_fix_jobs_approved_by_morph_idx');
            $table->dropColumn(['approved_by_id', 'approved_by_type']);
        });
    }
};
