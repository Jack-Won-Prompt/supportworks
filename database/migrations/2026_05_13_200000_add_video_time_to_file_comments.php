<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            // 동영상 의견의 재생 시간(초). null = 일반 의견
            $table->decimal('video_time', 10, 2)->nullable()->after('page');
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropColumn('video_time');
        });
    }
};
