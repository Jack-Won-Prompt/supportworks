<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('company_group_id')->nullable()->index();
            $table->string('team_name', 100)->nullable();
            $table->string('author_name', 100);
            $table->date('report_date');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('week_number');
            $table->date('week_start_date');
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->longText('summary')->nullable();
            $table->text('special_notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id', 'week_start_date'], 'wr_unique_user_week');
            $table->index(['project_id', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
