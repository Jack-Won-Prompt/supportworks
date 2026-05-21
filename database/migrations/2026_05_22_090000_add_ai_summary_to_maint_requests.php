<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SR 등록/수정 화면의 [웍스 요약 생성] 결과를 저장.
 *
 * 같은 회사의 비슷한 유형(category+menu) 기존 SR 을 컨텍스트로 묶어
 * AI 가 정리한 요약문. 재요약 시 덮어쓰기 (이력은 git/문서에서 추적).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $t) {
            $t->text('ai_summary')->nullable()->after('content');
            $t->timestamp('ai_summary_at')->nullable()->after('ai_summary');
            $t->json('ai_summary_context_ids')->nullable()->after('ai_summary_at');
        });
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $t) {
            $t->dropColumn(['ai_summary', 'ai_summary_at', 'ai_summary_context_ids']);
        });
    }
};
