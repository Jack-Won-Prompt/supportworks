<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompt_sessions', function (Blueprint $table) {
            $table->renameColumn('task_id', 'schedule_id');
        });

        Schema::table('prompt_histories', function (Blueprint $table) {
            $table->renameColumn('task_id', 'schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('prompt_sessions', function (Blueprint $table) {
            $table->renameColumn('schedule_id', 'task_id');
        });

        Schema::table('prompt_histories', function (Blueprint $table) {
            $table->renameColumn('schedule_id', 'task_id');
        });
    }
};
