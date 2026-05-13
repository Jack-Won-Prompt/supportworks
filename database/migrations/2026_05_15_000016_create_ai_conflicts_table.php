<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_agent_sessions')->cascadeOnDelete();
            $table->foreignId('output_id')->nullable()->constrained('ai_outputs')->nullOnDelete();

            // AgentConflictType
            $table->string('conflict_type', 64);
            // AgentConflictSeverity: low | medium | high | critical
            $table->string('severity', 16)->default('medium');

            $table->text('description');

            // AI가 제안한 선택지 목록 (id/label/preview)
            $table->json('suggested_options_json')->nullable();
            // 사용자 결정 — 선택지 ID 또는 자유 문자열
            $table->string('user_decision', 255)->nullable();
            $table->text('user_decision_note')->nullable();

            // open | resolved | dismissed
            $table->string('status', 32)->default('open');

            $table->timestamps();

            $table->index(['session_id', 'status']);
            $table->index(['output_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conflicts');
    }
};
