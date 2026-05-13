<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_planning_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100);
            $table->string('name');
            $table->string('version', 20)->default('1.0.0');
            $table->text('description')->nullable();
            $table->json('structure')->nullable();
            $table->string('template_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['key', 'version']);
            $table->index(['is_active', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_planning_templates');
    }
};
