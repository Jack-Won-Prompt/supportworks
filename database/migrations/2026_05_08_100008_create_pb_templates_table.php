<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->enum('share_scope', ['private', 'team', 'public'])->default('private');
            $table->json('context_template')->nullable();
            $table->json('purpose_template')->nullable();
            $table->json('builder_structure')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'share_scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_templates');
    }
};
