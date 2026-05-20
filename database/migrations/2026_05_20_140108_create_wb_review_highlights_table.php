<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §1.6 — wb_review_highlights.
 *
 * 검수 차수별 하이라이트(요소 선택) 이력.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_review_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_session_id')
                ->constrained('wb_review_sessions')->cascadeOnDelete();
            $table->string('selector_path', 512);
            $table->string('tag_name', 32);
            $table->string('classes', 512)->nullable();
            $table->text('text_snippet')->nullable();
            $table->integer('bbox_x')->nullable();
            $table->integer('bbox_y')->nullable();
            $table->integer('bbox_w')->nullable();
            $table->integer('bbox_h')->nullable();
            $table->timestamps();

            $table->index('review_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_review_highlights');
    }
};
