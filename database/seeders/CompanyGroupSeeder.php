<?php

namespace Database\Seeders;

use App\Models\CompanyGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanyGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name'        => 'SupportWorks',
                'code'        => 'SUPPORTWORKS',
                'description' => 'SupportWorks 내부 팀 (관리자·멤버)',
                'is_active'   => true,
                'companies'   => ['SupportWorks'],
            ],
            [
                'name'        => 'ABC Corp',
                'code'        => 'ABCCORP',
                'description' => 'ABC Corp 외부 사용자 그룹',
                'is_active'   => true,
                'companies'   => ['ABC Corp'],
            ],
            [
                'name'        => 'XYZ Inc',
                'code'        => 'XYZINC',
                'description' => 'XYZ Inc 외부 사용자 그룹',
                'is_active'   => true,
                'companies'   => ['XYZ Inc'],
            ],
        ];

        foreach ($groups as $data) {
            $companies = $data['companies'];
            unset($data['companies']);

            $group = CompanyGroup::firstOrCreate(
                ['code' => $data['code']],
                $data
            );

            // 기존 사용자 중 company 필드가 일치하는 사용자를 그룹에 배정
            $updated = User::whereIn('company', $companies)
                ->whereNull('company_group_id')
                ->update(['company_group_id' => $group->id]);

            $total = User::where('company_group_id', $group->id)->count();
            $this->command->line("  [{$group->code}] {$group->name} — 사용자 {$total}명 배정");
        }
    }
}
