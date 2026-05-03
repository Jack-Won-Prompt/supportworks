<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_approval_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('ai_agent_project_stages')->cascadeOnDelete();
            $table->foreignId('artifact_id')->nullable()->constrained('ai_agent_artifacts')->nullOnDelete();
            $table->enum('gate_type', ['stage_completion', 'artifact_approval'])->default('artifact_approval');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('requested_at')->useCurrent();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('request_comment')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['stage_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_approval_gates');
    }
};
