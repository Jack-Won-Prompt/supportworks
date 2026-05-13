<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->string('guest_name', 100)->nullable()->after('user_id');
        });

        Schema::table('file_annotations', function (Blueprint $table) {
            $table->string('guest_name', 100)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropColumn('guest_name');
        });

        Schema::table('file_annotations', function (Blueprint $table) {
            $table->dropColumn('guest_name');
        });
    }
};
