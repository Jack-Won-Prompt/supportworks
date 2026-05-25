<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 신규 SR 등록 직후 자동 AI 분석 + 등록자 확인 워크플로우.
 *
 * 흐름:
 *   1) 요청자가 SR 등록 → status='ai_review' 로 진입
 *   2) SrAiReviewService 가 동기로 분석 → ai_review_* 컬럼 채움
 *   3) 요청자가 원본 vs AI 정리본 비교 + (AI 자동 질문 있으면) 답변 후 "이대로 요청"
 *   4) 확인 완료 시 status='requested' 로 전환되어 담당자 큐로 진입
 *
 * status 컬럼은 이미 VARCHAR(32) 이라 enum 변경 불필요.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            // AI 가 재정리한 요청 본문(요청자 확인용 — 담당자용 ai_summary 와 별도)
            $table->text('ai_review_summary')->nullable()->after('ai_summary_context_ids');
            // AI 추천 난이도 (1~5) — difficulty_score 와 별개. 확인 시 사용자 채택 가능.
            $table->unsignedTinyInteger('ai_review_difficulty')->nullable()->after('ai_review_summary');
            // AI 추정 작업량 — "0.5일", "1~2일" 등 자연어
            $table->string('ai_review_effort', 50)->nullable()->after('ai_review_difficulty');
            // AI 자동 의문점: [{q:string, a:?string}] — 요청자가 답변하면 a 채워짐
            $table->json('ai_review_questions')->nullable()->after('ai_review_effort');
            // 분석 상태: pending / analyzing / ready / confirmed / failed
            $table->string('ai_review_status', 20)->nullable()->after('ai_review_questions');
            $table->dateTime('ai_review_at')->nullable()->after('ai_review_status');
            // 실패 사유 (failed 일 때만)
            $table->text('ai_review_error')->nullable()->after('ai_review_at');
        });
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->dropColumn([
                'ai_review_summary',
                'ai_review_difficulty',
                'ai_review_effort',
                'ai_review_questions',
                'ai_review_status',
                'ai_review_at',
                'ai_review_error',
            ]);
        });
    }
};
