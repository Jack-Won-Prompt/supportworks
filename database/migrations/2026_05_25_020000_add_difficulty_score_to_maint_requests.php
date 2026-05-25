<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            // 난이도 점수 (1~5). 명세서 SR_담당자_주간성과지표.md §3.2 참조.
            // fulfillment 58단위 난이도 표 매핑 (현재는 단순 1~5 점수만 저장).
            // null = 미매핑. 가중 처리량 계산 시 가중치 0 (또는 분리 카운트).
            $table->unsignedTinyInteger('difficulty_score')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->dropColumn('difficulty_score');
        });
    }
};
