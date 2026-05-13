<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('name');
            $table->enum('role', ['admin', 'operator', 'support_agent'])->default('support_agent');
            $table->string('token', 80)->unique();
            $table->enum('status', ['invited', 'accepted', 'expired', 'disabled'])->default('invited');
            $table->foreignId('invited_by')->constrained('admin_users')->cascadeOnDelete();
            $table->json('company_group_ids')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_invitations');
    }
};
