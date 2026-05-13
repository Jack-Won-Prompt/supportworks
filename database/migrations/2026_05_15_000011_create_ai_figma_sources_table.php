<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_figma_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('ai_agent_sessions')->cascadeOnDelete();

            // figma_url | project_file | existing_source
            $table->string('source_type', 32)->default('figma_url');

            $table->string('figma_url', 1000)->nullable();
            $table->string('figma_file_key', 64)->nullable();
            $table->string('figma_node_id', 64)->nullable();
            $table->string('figma_version', 64)->nullable();

            // OAuth로 연결됐다면 어떤 사용자의 토큰을 사용했는지 기록
            $table->foreignId('oauth_user_id')->nullable()->constrained('users')->nullOnDelete();

            // disconnected | connected | invalid | unauthorized | unreachable
            $table->string('status', 32)->default('disconnected');
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['session_id', 'status']);
            $table->index(['project_id', 'figma_file_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_figma_sources');
    }
};
