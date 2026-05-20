<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.1 — wb_internal_prompts.
 *
 * AI에 전송한 system + user 페이로드 원본 로그. 재현·감사용.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_internal_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->enum('purpose', [
                'spec_generation', 'html_generation', 'regeneration', 'reopen',
            ]);
            $table->unsignedInteger('review_round')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->longText('user_prompt');
            $table->json('payload_metadata')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_internal_prompts');
    }
};
