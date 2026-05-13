<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['general', 'project'])->default('general');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project_code')->nullable();
            $table->string('weekly_department')->nullable();
            $table->datetime('meeting_date');
            $table->string('location')->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_group_id')->nullable()->constrained('company_groups')->nullOnDelete();
            $table->text('agenda')->nullable();
            $table->text('discussion')->nullable();
            $table->text('decisions')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};
