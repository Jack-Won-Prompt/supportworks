<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $t) {
            $t->unsignedBigInteger('company_group_id')->nullable()->after('menu_id');
            $t->index('company_group_id', 'idx_maint_requests_company_group');
        });

        // 현 시점 데이터는 전부 콜로플라스트 소속 — 자동 백필
        $cgId = DB::table('company_groups')->where('name', '콜로플라스트')->value('id');
        if ($cgId) {
            DB::table('maint_requests')->whereNull('company_group_id')->update(['company_group_id' => $cgId]);
        }
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $t) {
            $t->dropIndex('idx_maint_requests_company_group');
            $t->dropColumn('company_group_id');
        });
    }
};
