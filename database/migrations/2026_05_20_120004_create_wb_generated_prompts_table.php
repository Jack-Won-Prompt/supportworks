<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_generated_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->enum('target_ai', ['cursor', 'claude', 'openai']);
            // 옵션 입력 기반 / 기획서 기반
            $table->enum('source_mode', ['options', 'spec']);
            $table->unsignedInteger('version')->default(1);
            $table->longText('prompt_text');
            // 생성 시 사용된 옵션·스펙 스냅샷
            $table->json('source_snapshot')->nullable();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'target_ai', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_generated_prompts');
    }
};
