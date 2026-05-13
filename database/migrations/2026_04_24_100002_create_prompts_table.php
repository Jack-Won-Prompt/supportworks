<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('prompt_categories')->nullOnDelete();
            $table->string('name', 200);
            $table->string('type', 100)->nullable();
            $table->text('purpose')->nullable();
            $table->text('ai_role')->nullable();
            $table->text('input_data')->nullable();
            $table->text('conditions')->nullable();
            $table->text('output_format')->nullable();
            $table->longText('final_prompt');
            $table->float('confidence_score')->default(1.0);
            $table->enum('status', ['draft', 'approved'])->default('approved');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
