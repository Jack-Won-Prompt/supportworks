<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SR 등록 시 AI 가 분석한 분류값을 보관.
 *   free    : 무상 (에러 / 데이터 확인)
 *   paid    : 유상 추가 개발 (기능 추가 / 프로세스 변경 / 추가 기능)
 *   discuss : 논의 필요
 *
 * 상세 화면의 '추가 개발 (유상)' 박스 상단에 배지로 표시.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->string('ai_classification', 20)->nullable()->after('ai_summary_context_ids');
        });
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->dropColumn('ai_classification');
        });
    }
};
