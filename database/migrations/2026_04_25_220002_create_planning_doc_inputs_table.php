<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_doc_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_doc_id')->constrained()->cascadeOnDelete();
            $table->enum('input_type', ['text','memo','requirement','file']);
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->enum('status', ['pending','processed'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_doc_inputs');
    }
};
