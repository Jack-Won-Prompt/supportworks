<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'analyzing', 'confirmed', 'changed', 'deferred', 'cancelled'])->default('draft');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->enum('category', ['functional', 'non_functional', 'ui', 'data', 'security'])->default('functional');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->constrained('users');
            $table->json('tags')->nullable();

            // SI 모드 필드
            $table->enum('requirement_type', ['initial', 'additional', 'change'])->default('initial');
            $table->string('source_ref')->nullable();
            $table->enum('approval_status', ['reviewing', 'approved', 'rejected', 'returned'])->default('reviewing');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // 2단계(웍스 분석) 준비 필드
            $table->enum('source_type', ['manual', 'ai_analyzed'])->default('manual');
            $table->unsignedBigInteger('source_session_id')->nullable();

            // 3단계(기획서 연동) 준비 필드
            $table->boolean('applied_to_plan')->default(false);
            $table->timestamp('applied_to_plan_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
