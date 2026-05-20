<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_ng_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_session_id')->constrained('wb_review_sessions')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->unsignedInteger('review_round');
            $table->text('note');
            $table->text('command_box')->nullable();
            $table->string('screenshot_path', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'review_round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_ng_inputs');
    }
};
