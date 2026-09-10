<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** AI Works — 작업 PC ↔ 프로젝트 매핑. local_path 가 데몬의 작업 루트가 된다. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_agent_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('aiw_agents')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('local_path');                 // PC 의 프로젝트 절대 경로
            $table->string('default_branch')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_agent_projects');
    }
};
