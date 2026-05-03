<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 기존 prompts 테이블과 구분되는 AI Agent 전용 프롬프트 라이브러리
        Schema::create('ai_agent_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete(); // null = 전체 공통
            $table->enum('stage', ['planning', 'design', 'dev_prep', 'development', 'release', 'common']);
            $table->enum('task_type', [
                'as_is_analysis',
                'requirements_extraction',
                'gap_analysis',
                'planning_doc',
                'ia_flow',
                'screen_prompt',
                'mockup_generation',
                'design_consistency',
                'erd_generation',
                'api_spec',
                'rbac_model',
                'code_generation',
                'code_review',
                'custom',
            ]);
            $table->string('name', 100);
            $table->text('template');                    // {variable} 형식 변수 포함
            $table->json('variables')->nullable();        // [{"key":"var","description":"설명","required":true}]
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['stage', 'task_type', 'is_active']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_prompts');
    }
};
