<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('spec_reference_type')->nullable();
            $table->unsignedBigInteger('spec_reference_id')->nullable();
            $table->enum('mode', ['new', 'enhance']);
            $table->foreignId('assignee_id')->constrained('users')->cascadeOnDelete();
            $table->string('current_stage', 32)->default('started');
            $table->string('status', 32)->default('started');
            $table->unsignedInteger('current_review_round')->default(0);
            $table->string('output_type', 16)->default('html');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['spec_reference_type', 'spec_reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_tasks');
    }
};
