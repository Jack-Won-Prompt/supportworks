<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_error_logs', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('level')->index();
            $table->string('origin', 16)->nullable()->after('source')->index();
        });
    }

    public function down(): void
    {
        Schema::table('system_error_logs', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['origin']);
            $table->dropColumn(['source', 'origin']);
        });
    }
};
