<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_builder_id')->constrained('pb_builders')->cascadeOnDelete();
            $table->foreignId('to_builder_id')->constrained('pb_builders')->cascadeOnDelete();
            $table->enum('dependency_type', [
                'uses_standard', 'extends', 'references_output', 'shares_context',
            ]);
            $table->enum('strength', ['strong', 'medium', 'weak']);
            $table->boolean('auto_detected')->default(false);
            $table->decimal('confidence', 3, 2)->default(1.00);
            $table->timestamps();
            $table->unique(['from_builder_id', 'to_builder_id', 'dependency_type'], 'pb_dep_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_dependencies');
    }
};
