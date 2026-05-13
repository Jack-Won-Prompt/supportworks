<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_builders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('pb_workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('ai_type', ['cursor', 'claude', 'openai']);
            $table->enum('purpose_type', ['standard_assets', 'screen_generation', 'sequence_step']);
            $table->json('purpose_targets')->nullable();
            $table->string('figma_url')->nullable();
            $table->string('figma_file_path')->nullable();
            $table->json('input_source_files')->nullable();
            $table->json('input_images')->nullable();
            $table->json('applied_standards')->nullable();
            $table->longText('content');
            $table->boolean('is_edited')->default(false);
            $table->integer('current_version')->default(1);
            $table->unsignedBigInteger('sequence_id')->nullable();
            $table->integer('sequence_step_number')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'workspace_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_builders');
    }
};
