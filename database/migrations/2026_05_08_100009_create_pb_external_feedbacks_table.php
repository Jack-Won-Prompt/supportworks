<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_external_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('builder_id')->constrained('pb_builders')->cascadeOnDelete();
            $table->integer('builder_version');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('upload_method');
            $table->string('archive_path')->nullable();
            $table->json('uploaded_files')->nullable();
            $table->tinyInteger('user_rating')->nullable();
            $table->text('user_memo')->nullable();
            $table->json('analysis_result')->nullable();
            $table->json('applied_improvements')->nullable();
            $table->enum('status', ['uploaded', 'analyzing', 'analyzed', 'applied', 'rejected'])->default('uploaded');
            $table->timestamps();
            $table->index(['builder_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_external_feedbacks');
    }
};
