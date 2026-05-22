<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * maint_requests.status 를 ENUM → VARCHAR(32) 로 변경 + 빈값 복구.
 *
 * 이유: 직전 통합 마이그레이션에서 ENUM 정의에 없는 신규 값('reviewing',
 * 'additional_dev')으로 UPDATE 했더니 MySQL 이 빈 문자열로 강제 변환함.
 * 5유형(STATUSES) 운용을 위해 컬럼을 가변 길이 문자열로 풀어둠.
 */
return new class extends Migration {
    public function up(): void
    {
        // 컬럼을 VARCHAR(32)로 변경 (NOT NULL, 기본값 'requested')
        DB::statement("ALTER TABLE maint_requests MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'requested'");

        // ENUM 으로 인해 빈값('')이 된 레코드를 'reviewing' 으로 복구
        // (직전 마이그레이션의 reviewing 그룹: pending_check, review_*, discussion_needed, on_hold, awaiting_file, replied)
        DB::table('maint_requests')->where('status', '')->update(['status' => 'reviewing']);
    }

    public function down(): void
    {
        // 5유형 ENUM 으로 되돌림 (이전 13종 ENUM 으로 복원하지 않음)
        DB::statement("ALTER TABLE maint_requests MODIFY COLUMN status ENUM('requested','in_progress','additional_dev','reviewing','completed') NOT NULL DEFAULT 'requested'");
    }
};
