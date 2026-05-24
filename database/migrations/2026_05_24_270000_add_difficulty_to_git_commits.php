<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            // 0.0 ~ 5.0 휴리스틱 난이도 점수 (LOC + 파일 수 + 키워드 가중치)
            $table->decimal('difficulty', 3, 1)->nullable()->after('deletions');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            $table->dropIndex(['difficulty']);
            $table->dropColumn('difficulty');
        });
    }
};
