<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── community_posts ──────────────────────────────────────────────
        Schema::table('community_posts', function (Blueprint $table) {
            $table->foreignId('company_group_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('company_groups')
                  ->nullOnDelete();
        });

        // ── projects ─────────────────────────────────────────────────────
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('company_group_id')
                  ->nullable()
                  ->after('created_by')
                  ->constrained('company_groups')
                  ->nullOnDelete();
        });

        // ── 기존 데이터 백필: 작성자 user 의 company_group_id 를 복사 ──
        \DB::statement('
            UPDATE community_posts cp
            JOIN users u ON cp.user_id = u.id
            SET cp.company_group_id = u.company_group_id
            WHERE u.company_group_id IS NOT NULL
        ');

        \DB::statement('
            UPDATE projects p
            JOIN users u ON p.created_by = u.id
            SET p.company_group_id = u.company_group_id
            WHERE u.company_group_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropForeign(['company_group_id']);
            $table->dropColumn('company_group_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['company_group_id']);
            $table->dropColumn('company_group_id');
        });
    }
};
