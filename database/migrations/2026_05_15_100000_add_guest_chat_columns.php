<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('guest_token', 80)->nullable()->after('assigned_admin_id');
            $table->index('guest_token');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['guest_token']);
            $table->dropColumn('guest_token');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_guest');
        });
    }
};
