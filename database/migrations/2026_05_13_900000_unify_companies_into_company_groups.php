<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) companies → company_groups 로 데이터 이전 (이름 매칭, 없으면 신규 생성)
        if (Schema::hasTable('companies')) {
            $companies = DB::table('companies')->get();
            $nameToGroupId = [];
            foreach ($companies as $c) {
                $name = trim((string) $c->name);
                if ($name === '') continue;
                $existing = DB::table('company_groups')
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                    ->first();
                if ($existing) {
                    $nameToGroupId[$c->id] = $existing->id;
                    continue;
                }
                $base = preg_replace('/[^a-z0-9]+/i', '-', Str::ascii($name));
                $base = trim(strtolower($base), '-') ?: 'grp';
                $code = mb_substr($base, 0, 40);
                $i = 0;
                while (DB::table('company_groups')->where('code', $code)->exists()) {
                    $i++;
                    $code = mb_substr($base, 0, 36) . '-' . $i;
                }
                $newId = DB::table('company_groups')->insertGetId([
                    'name'       => $name,
                    'code'       => $code,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $nameToGroupId[$c->id] = $newId;
            }

            // 2) users.company_id 가 가리키던 회사를 company_group_id 로 옮김
            if (Schema::hasColumn('users', 'company_id')) {
                foreach ($nameToGroupId as $oldCompanyId => $newGroupId) {
                    DB::table('users')
                        ->where('company_id', $oldCompanyId)
                        ->update(['company_group_id' => $newGroupId]);
                }
            }
        }

        // 3) users.company_id FK / 컬럼 / 인덱스 제거
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropForeign(['company_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['company_id']); } catch (\Throwable $e) {}
                $table->dropColumn('company_id');
            });
        }

        // 4) companies 테이블 제거
        Schema::dropIfExists('companies');
    }

    public function down(): void
    {
        // 되돌릴 수 없음 (데이터 손실 위험) — 빈 함수로 둠
    }
};
