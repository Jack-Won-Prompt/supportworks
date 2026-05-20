<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.5 — wb_generated_html.
 *
 * 차수별 HTML 버전 관리. 재실행 Task의 경우 source_html_id로 원본 참조.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_generated_html', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('review_round')->default(0);
            $table->enum('generated_by', ['claude', 'openai'])->default('claude');
            $table->foreignId('ai_call_log_id')->nullable()
                ->constrained('wb_ai_call_logs')->nullOnDelete();
            $table->longText('html_content');
            $table->char('html_hash', 64)->index();
            $table->foreignId('source_html_id')->nullable()
                ->constrained('wb_generated_html')->nullOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'review_round', 'version']);
        });

        // wb_ai_call_logs.generated_html_id FK는 순환 참조라 별도 ALTER로 추가
        Schema::table('wb_ai_call_logs', function (Blueprint $table) {
            $table->foreign('generated_html_id')
                ->references('id')->on('wb_generated_html')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wb_ai_call_logs', function (Blueprint $table) {
            $table->dropForeign(['generated_html_id']);
        });
        Schema::dropIfExists('wb_generated_html');
    }
};
