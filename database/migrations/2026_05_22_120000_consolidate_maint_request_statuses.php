<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SR 요청 상태값을 13종 → 5유형으로 통합.
 *
 *   requested      ← draft, requested
 *   in_progress    ← planned, in_progress
 *   additional_dev (유지)
 *   reviewing      ← pending_check, review_requested, review_again,
 *                    discussion_needed, on_hold, awaiting_file, replied
 *   completed      (유지)
 */
return new class extends Migration {
    public function up(): void
    {
        // requested 그룹
        DB::table('maint_requests')->where('status', 'draft')->update(['status' => 'requested']);

        // in_progress 그룹
        DB::table('maint_requests')->where('status', 'planned')->update(['status' => 'in_progress']);

        // reviewing 그룹 (단일 값으로 모두 통합)
        DB::table('maint_requests')
            ->whereIn('status', [
                'pending_check', 'review_requested', 'review_again',
                'discussion_needed', 'on_hold', 'awaiting_file', 'replied',
            ])
            ->update(['status' => 'reviewing']);
    }

    public function down(): void
    {
        // 비가역(여러 값이 단일 값으로 합쳐졌으므로 원복 불가)
        // 의미상 가장 가까운 기본값으로 되돌림
        DB::table('maint_requests')->where('status', 'reviewing')->update(['status' => 'pending_check']);
        // requested 와 in_progress 는 그대로 둠 (구분 불가능)
    }
};
