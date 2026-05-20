<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.7 — wb_checklist_items.
 *
 * 프로젝트별 표준 체크 프롬프트. 모든 AI 호출에 자동 포함.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('category', [
                'html_structure', 'semantic', 'class_naming',
                'design_tokens', 'typography', 'accessibility',
            ]);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('check_prompt_text');

            $table->foreignId('added_from_task_id')->nullable()
                ->constrained('wb_tasks')->nullOnDelete();
            $table->unsignedInteger('added_from_round')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_checklist_items');
    }
};
