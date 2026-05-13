<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->decimal('ai_confidence', 3, 2)->nullable()->after('source_session_id');
            $table->boolean('user_modified')->default(false)->after('ai_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->dropColumn(['ai_confidence', 'user_modified']);
        });
    }
};
