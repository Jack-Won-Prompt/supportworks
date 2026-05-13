<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('framework');
            $table->string('framework_version')->nullable();
            $table->enum('language', ['typescript', 'javascript']);
            $table->string('styling')->nullable();
            $table->json('additional_config')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['project_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_workspaces');
    }
};
