<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_maintenance_replies', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->nullable()->after('user_id')
                  ->constrained('admin_users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('project_maintenance_replies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_user_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
