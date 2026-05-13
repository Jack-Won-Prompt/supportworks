<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('last_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->enum('last_ai_type', ['cursor', 'claude', 'openai'])->nullable();
            $table->json('per_project_workspace')->nullable();
            $table->boolean('auto_select_project')->default(true);
            $table->boolean('auto_select_workspace')->default(true);
            $table->boolean('auto_select_ai')->default(true);
            $table->integer('expiration_days')->default(30);
            $table->json('skip_confirm_dialogs')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_user_preferences');
    }
};
