<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.10 — wb_layout_previews.
 *
 * 옵션 스냅샷별 SVG 와이어프레임. AI 프롬프트엔 포함되지 않음 (§1.4 금지).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_layout_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->foreignId('task_options_id')->nullable()
                ->constrained('wb_task_options')->nullOnDelete();
            $table->json('options_snapshot')->nullable();
            $table->text('preview_svg')->nullable();
            $table->json('preview_metadata')->nullable();
            $table->timestamps();

            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_layout_previews');
    }
};
