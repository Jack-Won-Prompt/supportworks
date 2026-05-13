<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_wizard_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('current_step')->default(1);
            $table->json('completed_steps')->nullable();
            $table->json('context')->nullable();
            $table->json('purpose')->nullable();
            $table->json('input_sources')->nullable();
            $table->json('analysis_result')->nullable();
            $table->json('generated_builders')->nullable();
            $table->json('approved_changes')->nullable();
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_wizard_sessions');
    }
};
