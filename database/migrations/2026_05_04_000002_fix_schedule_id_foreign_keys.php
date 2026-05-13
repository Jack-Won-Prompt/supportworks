<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompt_sessions', function (Blueprint $table) {
            $table->dropForeign('prompt_sessions_task_id_foreign');
            $table->foreign('schedule_id')
                  ->references('id')->on('legacy_schedules')
                  ->onDelete('set null');
        });

        Schema::table('prompt_histories', function (Blueprint $table) {
            $table->dropForeign('prompt_histories_task_id_foreign');
            $table->foreign('schedule_id')
                  ->references('id')->on('legacy_schedules')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('prompt_sessions', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->foreign('schedule_id')
                  ->references('id')->on('tasks')
                  ->onDelete('set null');
        });

        Schema::table('prompt_histories', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->foreign('schedule_id')
                  ->references('id')->on('tasks')
                  ->onDelete('set null');
        });
    }
};
