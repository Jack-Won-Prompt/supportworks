<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_artifact_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artifact_id')->constrained('ai_agent_artifacts')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->enum('file_type', ['text', 'excel', 'pptx', 'pdf', 'image', 'other']);
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->string('storage_path', 500);
            $table->longText('parsed_content')->nullable();    // JSON
            $table->enum('parse_status', ['pending', 'parsing', 'completed', 'failed'])->default('pending');
            $table->text('parse_error')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index('artifact_id');
            $table->index('parse_status');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_artifact_files');
    }
};
