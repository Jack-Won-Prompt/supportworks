<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) summary_type enum → varchar (custom 추가)
        DB::statement("ALTER TABLE weekly_ai_summaries MODIFY summary_type VARCHAR(20) NOT NULL DEFAULT 'full'");

        Schema::table('weekly_ai_summaries', function (Blueprint $table) {
            // custom 타입에 사용: 기간 시작/종료
            $table->date('range_start')->nullable()->after('week_start_date');
            $table->date('range_end')->nullable()->after('range_start');
            // 정량 지표 (담당자별 표, 총계 등) — content 와 별도 저장해 재표시 시 재계산 불필요
            $table->json('metrics')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_ai_summaries', function (Blueprint $table) {
            $table->dropColumn(['range_start', 'range_end', 'metrics']);
        });
        DB::statement("ALTER TABLE weekly_ai_summaries MODIFY summary_type ENUM('full','weekly') NOT NULL");
    }
};
