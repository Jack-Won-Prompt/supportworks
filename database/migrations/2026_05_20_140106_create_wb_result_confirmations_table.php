<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §1.5 — wb_result_confirmations.
 *
 * AI 생성 결과 1차 확인 이력.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_result_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->foreignId('generated_html_id')
                ->constrained('wb_generated_html')->cascadeOnDelete();
            $table->enum('decision', ['regenerate', 'proceed_to_review']);
            $table->text('note')->nullable();
            $table->foreignId('confirmed_by')->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_result_confirmations');
    }
};
