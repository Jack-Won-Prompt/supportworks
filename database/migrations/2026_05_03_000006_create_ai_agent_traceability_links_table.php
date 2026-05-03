<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_traceability_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->enum('source_type', ['requirement', 'screen', 'component', 'api_endpoint', 'code_file', 'artifact']);
            $table->unsignedBigInteger('source_id');
            $table->string('source_ref', 50)->nullable();    // REQ-001, SCR-001 등 사람이 읽는 ID
            $table->enum('target_type', ['requirement', 'screen', 'component', 'api_endpoint', 'code_file', 'artifact']);
            $table->unsignedBigInteger('target_id');
            $table->string('target_ref', 50)->nullable();
            $table->enum('link_type', ['implements', 'designs', 'tests', 'documents', 'depends_on'])->default('implements');
            $table->timestamps();

            $table->index(['project_id', 'source_type', 'source_id'], 'tl_source_idx');
            $table->index(['project_id', 'target_type', 'target_id'], 'tl_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_traceability_links');
    }
};
