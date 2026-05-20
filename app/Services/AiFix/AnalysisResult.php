<?php

namespace App\Services\AiFix;

/**
 * AI 분석기(또는 PoC stub)가 SystemErrorLog 한 건을 분석한 결과.
 * AiFixOrchestrator 가 이걸 받아 FixContext 를 구성한다.
 */
final class AnalysisResult
{
    public function __construct(
        /** 에러 카테고리 (예: 'null_check', 'type_mismatch', 'unknown') */
        public readonly string $category,

        /** 분류 신뢰도 0.0 ~ 1.0 */
        public readonly float $confidence,

        /** 수정 대상으로 제안하는 상대 경로 파일 목록 */
        public readonly array $changedFiles,

        /** 사람이 읽을 수 있는 변경 요약 (관리자 알림/모바일 UI에 표시) */
        public readonly string $summary,

        /** AI 자체 평가에서 "잘 모르겠음" 플래그 */
        public readonly bool $unsure = false,
    ) {}
}