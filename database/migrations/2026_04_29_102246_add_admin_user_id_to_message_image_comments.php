<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_image_comments', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete()->after('user_id');
            $table->string('admin_name', 100)->nullable()->after('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_image_comments', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->dropColumn(['admin_user_id', 'admin_name']);
        });
    }
};
