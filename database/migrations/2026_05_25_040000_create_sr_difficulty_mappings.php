<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세서 SR_담당자_주간성과지표.md §8.1
 *
 * SR ↔ fulfillment 난이도 표 58단위 매핑 테이블.
 * 한 SR 이 여러 단위에 걸칠 수 있어 1:N 관계.
 * 분석 시 동일 SR 의 매핑 중 **최고 score** 적용 (명세 §3.2 규칙 2).
 *
 * 현재 maint_requests.difficulty_score 컬럼은 derived cache 로 유지
 * (sr_difficulty_mappings 의 MAX(score) 를 캐시. 매핑 변경 시 자동 갱신).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sr_difficulty_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sr_id');     // maint_requests.id
            $table->unsignedSmallInteger('difficulty_unit_no');  // fulfillment §13 1~58
            $table->unsignedTinyInteger('score');    // 1~5
            $table->unsignedBigInteger('mapped_by')->nullable();   // users.id
            $table->dateTime('mapped_at')->nullable();
            $table->timestamps();

            $table->foreign('sr_id')->references('id')->on('maint_requests')->cascadeOnDelete();
            $table->index('sr_id', 'idx_srdm_sr');
            $table->index('difficulty_unit_no', 'idx_srdm_unit');
            $table->unique(['sr_id', 'difficulty_unit_no'], 'uniq_srdm_sr_unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sr_difficulty_mappings');
    }
};
