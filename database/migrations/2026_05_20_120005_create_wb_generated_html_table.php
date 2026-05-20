<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_generated_html', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('review_round')->default(0);
            $table->enum('source_ai_tool', ['cursor', 'claude', 'openai', 'other'])->default('other');
            $table->longText('html_content');
            $table->char('html_hash', 64);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['task_id', 'review_round', 'version']);
            $table->index('html_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_generated_html');
    }
};
