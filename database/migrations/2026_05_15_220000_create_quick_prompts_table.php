<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quick_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('original_input');
            $table->longText('refined_prompt')->nullable();
            $table->boolean('append_confirmation')->default(false);
            $table->string('provider_used', 30)->nullable();
            $table->string('model_used', 60)->nullable();
            $table->string('fallback_reason', 200)->nullable();
            $table->unsignedInteger('elapsed_ms')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_prompts');
    }
};
