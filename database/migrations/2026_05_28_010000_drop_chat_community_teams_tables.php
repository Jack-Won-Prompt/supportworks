<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 웍스 채팅 + 커뮤니티 + Teams 기능 폐기 — 관련 테이블 일괄 DROP.
 *
 * 보존:
 *   - ai_settings (모든 AI 기능 공통)
 *   - ai_fix_jobs (관리자 시스템 에러 AI 자동 수정)
 *   - ai-agent 관련 테이블 전체 (ai_agent_*, ai_outputs 등) — 웍스 개발 Agent 유지
 *
 * down() 복구 미지원 — 백업 필요 시 sw.sql dump 로 복원.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            // 커뮤니티
            'community_comments',
            'community_reactions',
            'community_votes',
            'community_posts',

            // 웍스 채팅 (Prompt 라이브러리·Figma·세션·메시지)
            'execution_files',
            'prompt_executions',
            'prompts',
            'prompt_categories',
            'figma_files',
            'ai_project_files',
            'ai_messages',
            'ai_sessions',

            // Teams 연동
            'teams_settings',
        ];

        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // 복구 미지원 — sw.sql dump 로 복원 필요
    }
};
