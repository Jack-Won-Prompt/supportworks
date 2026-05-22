<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * colo_user_id 가 NULL 인 SR 들을 콜로플라스트 '대리점' 사용자로 매핑.
 *
 *  1) '대리점' MaintUser(team='colo') 가 없으면 생성, user_id = '대리점' User
 *  2) maint_requests.colo_user_id IS NULL → 위 MaintUser.id 로 업데이트
 */
return new class extends Migration {
    public function up(): void
    {
        $coloCompanyId = (int) DB::table('company_groups')->where('name', '콜로플라스트')->value('id');
        $agencyUserId  = $coloCompanyId
            ? DB::table('users')->where('company_group_id', $coloCompanyId)->where('name', '대리점')->value('id')
            : null;

        // '대리점' MaintUser 확보 (없으면 생성)
        $agencyMu = DB::table('maint_users')
            ->where('team', 'colo')
            ->where('name', '대리점')
            ->first();

        if ($agencyMu) {
            $agencyMuId = $agencyMu->id;
            if ($agencyUserId && empty($agencyMu->user_id)) {
                DB::table('maint_users')->where('id', $agencyMuId)->update(['user_id' => $agencyUserId]);
            }
        } else {
            $agencyMuId = DB::table('maint_users')->insertGetId([
                'name'       => '대리점',
                'team'       => 'colo',
                'user_id'    => $agencyUserId,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // colo_user_id NULL 인 SR 들을 '대리점' 으로 매핑
        DB::table('maint_requests')->whereNull('colo_user_id')->update(['colo_user_id' => $agencyMuId]);
    }

    public function down(): void
    {
        // 비가역(어떤 SR이 원래 NULL이었는지 구분 불가능)
    }
};
