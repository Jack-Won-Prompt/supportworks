<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();           // 승인된 현재 본문
            $table->longText('pending_content')->nullable();   // 웍스 통합 후 검토 대기
            $table->text('ai_summary')->nullable();
            $table->text('ai_conflicts')->nullable();
            $table->text('ai_suggestions')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft','ai_processed','pending_review','approved','rejected'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_docs');
    }
};
