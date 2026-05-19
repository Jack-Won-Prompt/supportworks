<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            // 산출물 의견 반영 — '반영됨' 상태 추적
            $table->timestamp('reflected_at')->nullable();
            $table->foreignId('reflected_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropForeign(['reflected_by']);
            $table->dropColumn(['reflected_at', 'reflected_by']);
        });
    }
};
