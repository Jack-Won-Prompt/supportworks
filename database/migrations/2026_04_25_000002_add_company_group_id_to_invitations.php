<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->foreignId('company_group_id')
                  ->nullable()
                  ->after('invited_by')
                  ->constrained('company_groups')
                  ->nullOnDelete();
        });

        // 기존 초대에 초대자의 company_group_id 를 백필
        \DB::statement('
            UPDATE invitations i
            JOIN users u ON i.invited_by = u.id
            SET i.company_group_id = u.company_group_id
            WHERE u.company_group_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['company_group_id']);
            $table->dropColumn('company_group_id');
        });
    }
};
