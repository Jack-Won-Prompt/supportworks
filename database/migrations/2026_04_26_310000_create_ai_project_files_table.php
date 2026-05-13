<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('ai_sessions')->nullOnDelete();
            $table->string('file_name');           // index.html, style.css, script.js, main.php …
            $table->string('lang', 30)->default('html');
            $table->longText('content');
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'project_id', 'file_name']);
            $table->index(['user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_project_files');
    }
};
