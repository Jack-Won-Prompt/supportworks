<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_urs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['draft', 'qa_in_progress', 'generating', 'completed'])->default('draft');
            $table->json('qa_questions')->nullable();   // [{q, ai_suggestion, answer}, ...]
            $table->unsignedInteger('current_q_index')->default(0);
            $table->longText('content')->nullable();    // 완성된 Markdown URS
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_urs');
    }
};
