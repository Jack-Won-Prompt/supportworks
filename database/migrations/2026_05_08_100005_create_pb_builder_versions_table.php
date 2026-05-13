<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_builder_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('builder_id')->constrained('pb_builders')->cascadeOnDelete();
            $table->integer('version_number');
            $table->longText('content');
            $table->enum('created_by_type', ['user', 'system', 'feedback']);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users');
            $table->enum('change_reason', [
                'manual_edit', 'forward_propagation', 'feedback_learning', 'initial',
            ]);
            $table->text('change_description')->nullable();
            $table->string('triggered_by')->nullable();
            $table->json('changes_diff')->nullable();
            $table->boolean('is_reverted')->default(false);
            $table->integer('reverted_to_version')->nullable();
            $table->timestamps();
            $table->index(['builder_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_builder_versions');
    }
};
