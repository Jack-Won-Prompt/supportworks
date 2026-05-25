<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->dateTime('assigned_at')->nullable()->after('assignee_id');
            $table->unsignedInteger('reopen_count')->default(0)->after('completed_at');
        });

        // 기존 행 백필 — assignee_id 있는 SR 은 created_at 을 assigned_at 로
        DB::statement("UPDATE maint_requests SET assigned_at = created_at WHERE assignee_id IS NOT NULL AND assigned_at IS NULL");
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'reopen_count']);
        });
    }
};
