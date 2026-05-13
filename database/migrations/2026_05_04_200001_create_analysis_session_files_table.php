<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_session_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_session_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->longText('extracted_text')->nullable();
            $table->enum('extraction_status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('extraction_error')->nullable();
            $table->timestamp('uploaded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_session_files');
    }
};
