<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            // 커밋 단위 수정 파일 목록 (path, additions, deletions, status)
            // 예: [{"path":"app/Models/User.php","additions":5,"deletions":2,"status":"modified"}, ...]
            $table->json('files_json')->nullable()->after('files_changed');
        });
    }

    public function down(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            $table->dropColumn('files_json');
        });
    }
};
