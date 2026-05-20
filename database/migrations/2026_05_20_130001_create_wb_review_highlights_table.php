<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_review_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_session_id')->constrained('wb_review_sessions')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->unsignedInteger('review_round');
            $table->string('selector_path', 500);
            $table->string('tag_name', 32);
            $table->string('classes', 500)->nullable();
            $table->string('text_snippet', 200)->nullable();
            $table->unsignedInteger('bbox_x');
            $table->unsignedInteger('bbox_y');
            $table->unsignedInteger('bbox_w');
            $table->unsignedInteger('bbox_h');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'review_round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_review_highlights');
    }
};
