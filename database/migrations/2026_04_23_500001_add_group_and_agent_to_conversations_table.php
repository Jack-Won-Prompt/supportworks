<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('company_group_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('company_groups')
                  ->nullOnDelete();

            // 담당 상담원 (users 테이블의 agent)
            $table->foreignId('assigned_agent_id')
                  ->nullable()
                  ->after('company_group_id')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['company_group_id']);
            $table->dropForeign(['assigned_agent_id']);
            $table->dropColumn(['company_group_id', 'assigned_agent_id']);
        });
    }
};
