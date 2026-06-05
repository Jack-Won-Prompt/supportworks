<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // 수정 시각 (null = 미수정), 삭제 시각 (null = 미삭제, soft tombstone)
            $table->timestamp('edited_at')->nullable()->after('translate_lang');
            $table->timestamp('deleted_at')->nullable()->after('edited_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['edited_at', 'deleted_at']);
        });
    }
};
