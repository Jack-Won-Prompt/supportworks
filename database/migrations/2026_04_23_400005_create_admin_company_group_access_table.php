<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_company_group_access', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('company_group_id')->constrained('company_groups')->cascadeOnDelete();
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_view_chats')->default(true);
            $table->timestamps();

            $table->primary(['admin_user_id', 'company_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_company_group_access');
    }
};
