<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_change_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->boolean('is_system_action')->default(false);
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->enum('change_type', ['create', 'update', 'delete', 'revert']);
            $table->enum('reason', ['manual', 'forward_flow', 'backward_flow', 'pattern_learning']);
            $table->string('triggered_by')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->longText('diff')->nullable();
            $table->json('affected_items')->nullable();
            $table->boolean('is_revertible')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_change_audit_logs');
    }
};
