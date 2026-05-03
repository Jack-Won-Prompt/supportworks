<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_project_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->enum('frontend_stack', ['html', 'react', 'vue']);
            $table->string('backend_stack', 50)->nullable();
            $table->boolean('ai_agent_enabled')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('frontend_stack');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_project_configs');
    }
};
