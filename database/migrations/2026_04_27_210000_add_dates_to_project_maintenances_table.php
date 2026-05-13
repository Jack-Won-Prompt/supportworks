<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_maintenances', function (Blueprint $table) {
            $table->date('requested_date')->nullable()->after('status');   // 사용자 요청일
            $table->date('due_date')->nullable()->after('requested_date'); // 사용자 희망 처리 기한
            $table->date('scheduled_date')->nullable()->after('due_date'); // 관리자 처리 예정일
        });
    }

    public function down(): void
    {
        Schema::table('project_maintenances', function (Blueprint $table) {
            $table->dropColumn(['requested_date', 'due_date', 'scheduled_date']);
        });
    }
};
