<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MaintUser(team='colo')의 user_id 를 콜로플라스트 회사 User 와 매핑.
 *
 * 1) 자동 매칭: 동일 회사·동일 이름이 1명일 때만 자동 연결
 * 2) 명시 매핑: 별칭(축약/테스트 계정)은 수동 지정 — 자동 매칭으로 못 찾는 케이스
 */
return new class extends Migration {
    public function up(): void
    {
        $coloCompanyId = (int) DB::table('company_groups')->where('name', '콜로플라스트')->value('id');
        if (!$coloCompanyId) return;

        // (1) 자동: MaintUser.name 과 정확히 같은 콜로플라스트 User 1명 → 연결
        $colos = DB::table('maint_users')
            ->where('team', 'colo')
            ->whereNull('user_id')
            ->select('id', 'name')
            ->get();

        foreach ($colos as $mu) {
            $name = trim((string) $mu->name);
            if ($name === '') continue;
            $userIds = DB::table('users')
                ->where('company_group_id', $coloCompanyId)
                ->where('name', $name)
                ->pluck('id');
            if ($userIds->count() === 1) {
                DB::table('maint_users')->where('id', $mu->id)->update(['user_id' => $userIds->first()]);
            }
        }

        // (2) 명시 매핑: 자동 매칭 안 되는 별칭/테스트 계정
        $explicitMap = [
            'Lisa'   => 'Lisa Chung',
            'Stella' => 'Stella 김선미',
            'test'   => 'Lisa Chung',   // 테스트 더미 → Lisa Chung 으로 통합
        ];
        foreach ($explicitMap as $aliasName => $realName) {
            $mu = DB::table('maint_users')
                ->where('team', 'colo')
                ->whereNull('user_id')
                ->where('name', $aliasName)
                ->first();
            if (!$mu) continue;

            $uid = DB::table('users')
                ->where('company_group_id', $coloCompanyId)
                ->where('name', $realName)
                ->value('id');
            if ($uid) {
                DB::table('maint_users')->where('id', $mu->id)->update(['user_id' => $uid]);
            }
        }
    }

    public function down(): void
    {
        // 매핑 해제는 하지 않음 (의도된 자동 채움)
    }
};
