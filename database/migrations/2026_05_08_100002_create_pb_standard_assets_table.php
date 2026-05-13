<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_standard_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('pb_workspaces')->cascadeOnDelete();
            $table->enum('asset_type', ['layout', 'component', 'css_token', 'js_utility']);
            $table->string('name');
            $table->string('version');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['workspace_id', 'asset_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_standard_assets');
    }
};
