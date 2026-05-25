<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            // git patch-id (동일 변경 내용 식별). sha 와 별개로 cherry-pick/revert/rebase 로
            // 같은 변경이 다른 sha 로 등장하는 경우 같은 patch_id 를 가짐.
            // 컨트롤러에서 distinct patch_id 기준으로 dedupe 가능.
            $table->string('patch_id', 64)->nullable()->after('sha');
            $table->index('patch_id', 'idx_git_commits_patch_id');
        });
    }

    public function down(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            $table->dropIndex('idx_git_commits_patch_id');
            $table->dropColumn('patch_id');
        });
    }
};
