<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('ai_agent_project_stages')->cascadeOnDelete();
            $table->enum('type', [
                'as_is_analysis',
                'to_be_requirements',
                'gap_analysis',
                'planning_doc',
                'ia_flow',
                'screen_prompts',
                'mockup',
                'design_tokens',
                'component_spec',
                'design_system_doc',
                'erd',
                'api_spec',
                'rbac_model',
                'frontend_code',
                'backend_code',
                'release_package',
            ]);
            $table->string('title', 255);
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'stage_id', 'type']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_artifacts');
    }
};
