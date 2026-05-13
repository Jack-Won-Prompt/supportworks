<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_standard_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('pb_workspaces')->cascadeOnDelete();
            $table->enum('asset_type', ['layout', 'component', 'css_token', 'js_utility']);
            $table->string('name');
            $table->longText('content');
            $table->string('source');
            $table->json('source_metadata')->nullable();
            $table->integer('observation_count')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected', 'merged'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_standard_candidates');
    }
};
