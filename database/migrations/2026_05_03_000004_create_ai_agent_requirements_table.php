<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('artifact_id')->nullable()->constrained('ai_agent_artifacts')->nullOnDelete();
            $table->string('req_id', 20);        // REQ-001 형식
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('priority', ['must', 'should', 'could', 'wont'])->default('should'); // MoSCoW
            $table->string('category', 100)->nullable();
            $table->enum('source', ['as_is', 'to_be', 'gap'])->default('to_be');
            $table->enum('status', ['draft', 'confirmed', 'deferred', 'removed'])->default('draft');
            $table->timestamps();

            $table->unique(['project_id', 'req_id']);
            $table->index(['project_id', 'priority']);
            $table->index(['project_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_requirements');
    }
};
