<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_users', function (Blueprint $t) {
            $t->unsignedBigInteger('company_group_id')->nullable()->after('user_id');
            $t->index('company_group_id', 'idx_maint_users_company_group');
        });
    }

    public function down(): void
    {
        Schema::table('maint_users', function (Blueprint $t) {
            $t->dropIndex('idx_maint_users_company_group');
            $t->dropColumn('company_group_id');
        });
    }
};
