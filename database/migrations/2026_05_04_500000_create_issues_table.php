<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['버그', '장애', '문의', '개선요청', '기타'])->default('기타');
            $table->enum('status', ['신규', '처리중', '해결', '검증중', '종결', '보류', '반려'])->default('신규');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->enum('severity', ['Critical', 'Major', 'Minor', 'Trivial'])->nullable();
            $table->enum('environment', ['운영', '스테이징', '개발'])->nullable();
            $table->foreignId('reporter_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tags')->nullable();
            // 해결 정보
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            // SLA (SM 모드)
            $table->timestamp('sla_due')->nullable();
            $table->boolean('sla_breached')->default(false);
            // 연결 요구사항
            $table->foreignId('linked_requirement_id')->nullable()->constrained('requirements')->nullOnDelete();
            // Q&A 전환 출처
            $table->foreignId('converted_from_question_id')->nullable()->constrained('questions')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
