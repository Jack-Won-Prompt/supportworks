<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('login_id', 50)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'operator', 'support_agent'])
                  ->default('support_agent');
            $table->enum('status', ['active', 'inactive', 'locked'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedTinyInteger('login_fail_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('must_change_pw')->default(false);
            $table->foreignId('invited_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
