<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_report_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_report_id')->constrained()->cascadeOnDelete();
            $table->enum('section', ['current_week', 'next_week']);
            $table->string('task_name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['completed', 'in_progress', 'pending', 'planned'])->default('pending');
            $table->json('original_data')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['weekly_report_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_report_tasks');
    }
};
