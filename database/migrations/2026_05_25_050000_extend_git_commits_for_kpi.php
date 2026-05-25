<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세서 SR_담당자_주간성과지표.md §8.4 git_commit_logs 컬럼 정합성.
 *
 * 현 git_commits 와의 매핑:
 *   repo          ↔ source ('withworks')
 *   message       ↔ subject + body
 *   added_loc     ↔ insertions
 *   deleted_loc   ↔ deletions
 *   files         ↔ files_changed
 *
 * 신규 추가:
 *   sr_ids   JSON   — 커밋 메시지에서 파싱한 [SR-xxxx] ID 배열
 *   is_merge boolean— 머지 커밋 여부 (명세 §4.4 제외 규칙용)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            $table->json('sr_ids')->nullable()->after('subject');
            $table->boolean('is_merge')->default(false)->after('sr_ids');
            $table->index('is_merge', 'idx_git_commits_is_merge');
        });
    }

    public function down(): void
    {
        Schema::table('git_commits', function (Blueprint $table) {
            $table->dropIndex('idx_git_commits_is_merge');
            $table->dropColumn(['sr_ids', 'is_merge']);
        });
    }
};
