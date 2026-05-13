<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_screens', function (Blueprint $table) {
            $table->dropForeign('ai_agent_screens_gantt_task_id_foreign');
        });

        Schema::rename('schedules', 'legacy_schedules');
    }

    public function down(): void
    {
        Schema::rename('legacy_schedules', 'schedules');

        Schema::table('ai_agent_screens', function (Blueprint $table) {
            $table->foreign('gantt_task_id')
                ->references('id')->on('schedules')
                ->nullOnDelete();
        });
    }
};
