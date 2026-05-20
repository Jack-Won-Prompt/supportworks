<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_layout_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->json('options_snapshot');
            $table->longText('preview_svg');
            $table->json('preview_metadata')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_layout_previews');
    }
};
