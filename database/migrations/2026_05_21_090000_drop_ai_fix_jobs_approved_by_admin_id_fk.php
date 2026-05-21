<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_fix_jobs.approved_by_admin_id 의 FK constraint(admin_users) 만 drop.
 *
 * 결함 배경: AI Fix 는 웹 admin(admin_users 가드) 과 모바일(users 가드 + role=admin)
 * 양쪽에서 승인 가능하도록 설계됐으나 FK 가 admin_users 로 고정돼 모바일 경로에서
 * Integrity constraint violation 발생.
 *
 * 컬럼·인덱스·코드는 그대로 두고 FK constraint 만 제거. 컬럼 의미는 잠정적으로
 * "admin 권한을 가진 어떤 사용자의 id" 로 확장. 본질적 polymorphic 분리는 별도
 * follow-up (approved_by_id + approved_by_type) 로 추후 정리.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_fix_jobs', function (Blueprint $table) {
            $table->dropForeign('ai_fix_jobs_approved_by_admin_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('ai_fix_jobs', function (Blueprint $table) {
            $table->foreign('approved_by_admin_id')
                  ->references('id')->on('admin_users')
                  ->nullOnDelete();
        });
    }
};
