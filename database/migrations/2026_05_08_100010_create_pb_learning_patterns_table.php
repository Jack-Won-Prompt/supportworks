<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_learning_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('pattern_id');
            $table->string('pattern_name');
            $table->enum('category', ['component', 'utility', 'pattern', 'naming', 'styling']);
            $table->text('description')->nullable();
            $table->integer('observation_count')->default(1);
            $table->timestamp('first_observed_at');
            $table->timestamp('last_observed_at');
            $table->json('observed_in_feedbacks')->nullable();
            $table->boolean('reached_threshold')->default(false);
            $table->enum('user_decision', ['promoted', 'rejected', 'pending'])->default('pending');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'pattern_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_learning_patterns');
    }
};
