<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §1.9 — wb_notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()
                ->constrained('wb_tasks')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()
                ->constrained('projects')->cascadeOnDelete();
            $table->string('stage_code', 32);
            $table->unsignedInteger('review_round')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('deep_link', 512)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'is_read']);
            $table->index('stage_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_notifications');
    }
};
