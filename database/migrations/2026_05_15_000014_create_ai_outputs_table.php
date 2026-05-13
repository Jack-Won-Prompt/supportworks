<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_agent_sessions')->cascadeOnDelete();
            $table->foreignId('analysis_step_id')->nullable()->constrained('ai_analysis_steps')->nullOnDelete();

            $table->unsignedSmallInteger('version_no')->default(1);

            // html | react | vue | blade  — 세션과 보통 동일하나 세션 변경 대비 사본
            $table->string('output_type', 16);

            // 생성된 파일 목록 (path, type, content, summary)
            $table->longText('files_json')->nullable();

            // ZIP 파일 storage 경로 (config('ai-agent.storage.disk'))
            $table->string('zip_path', 500)->nullable();
            // 미리보기 URL (HTML output 등) — 외부 접근 가능 URL이거나 라우트 식별자
            $table->string('preview_url', 1000)->nullable();

            // AgentOutputStatus enum
            $table->string('status', 32)->default('pending');

            // anthropic | openai | mock
            $table->string('generated_by', 32)->nullable();
            $table->string('model_used', 64)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->text('change_summary')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'version_no']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_outputs');
    }
};
