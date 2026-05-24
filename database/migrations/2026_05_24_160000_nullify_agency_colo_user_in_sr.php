<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SR 요청자가 '대리점'(MaintUser team='colo', name='대리점') 인 데이터를
 * '선택 안함'(NULL) 으로 되돌린다. 이전 마이그레이션
 * 2026_05_22_120400_assign_null_colo_user_to_agency 를 역전한다.
 */
return new class extends Migration {
    public function up(): void
    {
        $agencyMuId = DB::table('maint_users')
            ->where('team', 'colo')
            ->where('name', '대리점')
            ->value('id');

        if ($agencyMuId) {
            DB::table('maint_requests')
                ->where('colo_user_id', $agencyMuId)
                ->update(['colo_user_id' => null]);
        }
    }

    public function down(): void
    {
        // 비가역(어떤 SR이 원래 NULL이었는지 구분 불가능)
    }
};
