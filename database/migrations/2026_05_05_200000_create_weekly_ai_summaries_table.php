<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->enum('summary_type', ['full', 'weekly']);
            $table->date('week_start_date')->nullable()->comment('weekly 타입일 때만 사용');
            $table->longText('content');
            $table->timestamps();

            $table->index(['project_id', 'summary_type', 'week_start_date'], 'wais_project_type_week_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_ai_summaries');
    }
};
