<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planning_docs')->cascadeOnDelete();
            $table->foreignId('applied_by_id')->constrained('users');
            $table->timestamp('applied_at');
            $table->enum('insertion_position', ['end', 'beginning', 'after_section'])->default('end');
            $table->string('section_anchor')->nullable();
            $table->string('template_used')->default('default');
            $table->text('inserted_markdown');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_applications');
    }
};
