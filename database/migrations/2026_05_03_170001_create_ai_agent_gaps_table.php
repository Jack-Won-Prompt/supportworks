<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_gaps', function (Blueprint $table) {
            $table->id();
            $table->string('gap_id', 20);
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('artifact_id')->nullable()->constrained('ai_agent_artifacts')->nullOnDelete();
            $table->string('title', 255);
            $table->text('current_state')->nullable();
            $table->text('target_state')->nullable();
            $table->enum('category', ['보안', '기능', 'UX', '성능', '데이터', '인프라', '기타'])->default('기타');
            $table->enum('severity', ['high', 'medium', 'low'])->default('medium');
            $table->enum('estimated_effort', ['high', 'medium', 'low'])->nullable();
            $table->json('recommended_actions')->nullable();
            $table->json('related_requirement_ids')->nullable();  // ['REQ-001', 'REQ-003']
            $table->enum('source', ['ai', 'manual'])->default('ai');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'gap_id']);
            $table->index(['project_id', 'severity']);
            $table->index(['project_id', 'category']);
            $table->index('artifact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_gaps');
    }
};
