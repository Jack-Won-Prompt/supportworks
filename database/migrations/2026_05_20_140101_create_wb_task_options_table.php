<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.3 — wb_task_options.
 *
 * 옵션 변경 시 기존 row를 is_current=false로, 새 row를 version+1로 추가.
 * 스냅샷 이력 보존.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_task_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->json('options_data')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->foreignId('changed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'is_current']);
            $table->index(['task_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_task_options');
    }
};
