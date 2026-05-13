<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('is_group');   // null=일반메시지, 'inquiry'=문의
            $table->string('status', 20)->nullable()->after('type');     // 'open','active','closed'
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['type', 'status']);
        });
    }
};
