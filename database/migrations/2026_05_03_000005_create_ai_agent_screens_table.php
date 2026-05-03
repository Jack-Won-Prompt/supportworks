<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('artifact_id')->nullable()->constrained('ai_agent_artifacts')->nullOnDelete();
            $table->string('screen_id', 20);     // SCR-001 형식
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('figma_url', 1000)->nullable();
            $table->string('figma_frame_id', 255)->nullable();
            $table->string('figma_dev_mode_url', 1000)->nullable();
            $table->text('generation_prompt')->nullable();   // 화면 생성에 사용한 프롬프트
            $table->text('mockup_content')->nullable();      // 생성된 목업(HTML/JSX 등)
            $table->enum('stack', ['html', 'react', 'vue'])->nullable();
            $table->enum('status', ['draft', 'designed', 'approved'])->default('draft');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'screen_id']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_screens');
    }
};
