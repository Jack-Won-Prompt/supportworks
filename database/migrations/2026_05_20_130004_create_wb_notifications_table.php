<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_uuid')->unique();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('wb_tasks')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            // 명세 §1.9 stage 코드
            $table->string('stage_code', 32);
            $table->unsignedInteger('review_round')->nullable();
            $table->string('title', 255);
            $table->string('message', 500);
            $table->string('deep_link', 500)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'is_read', 'created_at']);
            $table->index(['task_id', 'stage_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_notifications');
    }
};
