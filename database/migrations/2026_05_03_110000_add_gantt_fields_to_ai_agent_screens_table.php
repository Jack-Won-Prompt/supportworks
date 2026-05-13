<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_screens', function (Blueprint $table) {
            $table->unsignedBigInteger('gantt_task_id')->nullable()->after('artifact_id');
            $table->foreign('gantt_task_id')->references('id')->on('schedules')->nullOnDelete();

            $table->enum('source', ['gantt', 'manual'])->default('manual')->after('order');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable()->after('source');
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->date('scheduled_start')->nullable()->after('assigned_to_user_id');
            $table->date('scheduled_end')->nullable()->after('scheduled_start');
            $table->timestamp('archived_at')->nullable()->after('scheduled_end');

            $table->index(['project_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_screens', function (Blueprint $table) {
            $table->dropForeign(['gantt_task_id']);
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropIndex(['project_id', 'archived_at']);
            $table->dropColumn(['gantt_task_id', 'source', 'assigned_to_user_id', 'scheduled_start', 'scheduled_end', 'archived_at']);
        });
    }
};
