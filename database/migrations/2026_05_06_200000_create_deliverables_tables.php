<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 산출물 메인 테이블
        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('type_id', 30);          // USR, FRS, InfoSec 등
            $table->integer('current_step')->default(1);
            $table->string('status', 20)->default('not_started'); // not_started | in_progress | completed
            $table->string('responsibility', 5)->default('B');    // B | A+B
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'type_id']);
        });

        // 단계별 입력 데이터 저장
        Schema::create('deliverable_step_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')->constrained('deliverables')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('field_key', 100);
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['deliverable_id', 'step_order', 'field_key']);
        });

        // 단계별 도구 결과 저장 (다이어그램, 매트릭스 등)
        Schema::create('deliverable_tool_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')->constrained('deliverables')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('tool_id', 30);
            $table->longText('result')->nullable(); // JSON
            $table->timestamps();

            $table->unique(['deliverable_id', 'step_order', 'tool_id'], 'dlv_tool_results_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverable_tool_results');
        Schema::dropIfExists('deliverable_step_data');
        Schema::dropIfExists('deliverables');
    }
};
