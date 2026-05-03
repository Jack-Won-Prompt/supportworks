<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_artifact_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artifact_id')->constrained('ai_agent_artifacts')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['artifact_id', 'version']);
            $table->index('artifact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_artifact_versions');
    }
};
