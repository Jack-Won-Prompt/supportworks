<?php

namespace App\Services\AiFix;

/**
 * AI Fix 평가를 위한 입력 컨텍스트.
 *
 * 에러 정보 + AI가 제안한 수정 메타데이터 + 테스트 결과를 한 곳에 모은 값 객체.
 * EscalationEvaluator 가 이걸 받아 결정을 내린다.
 */
final class FixContext
{
    public function __construct(
        /** @var string[] 수정 대상 파일의 상대 경로 목록 (예: ['app/Models/User.php']) */
        public readonly array $changedFiles = [],

        /** AI가 분류한 에러 카테고리 (예: 'null_check' / 'type_mismatch' / 'unknown') */
        public readonly string $errorCategory = 'unknown',

        /** AI 분류 신뢰도 (0.0 ~ 1.0) */
        public readonly float $classificationConfidence = 0.0,

        /** 같은 (exception+file+line) 에러가 최근 동일 윈도우 내 발생한 횟수 */
        public readonly int $sameErrorOccurrenceCount = 1,

        /** 에러 메시지 + 스택트레이스 합본 (외부 API/환경 키워드 매칭에 사용) */
        public readonly string $errorBlob = '',

        /** 테스트 통과 여부 (testsRan=false 일 땐 의미 없음) */
        public readonly bool $testsPassed = false,

        /** 테스트 커버리지 델타 (라인 단위, 0 이면 새로 커버된 라인 없음) */
        public readonly int $coverageDeltaLines = 0,

        // 테스트가 실제로 돌았는지. 분석 단계처럼 아직 안 돌린 상태에선 false 로 전달.
        // false 면 'tests_failed' / 'tests_pass_but_no_coverage_delta' 신호 평가 생략.
        public readonly bool $testsRan = false,

        /** AI 자기 평가가 unsure 인지 (LLM이 직접 보고) */
        public readonly bool $aiSelfUnsure = false,

        /** migration / schema 변경 포함 여부 (호출자가 판단해서 전달) */
        public readonly bool $touchesSchema = false,

        // 변경 파일 각각에 대응되는 기존 테스트 파일이 있는지.
        // 호출자(Orchestrator)가 'app/Foo/Bar.php' → 'tests/(Unit|Feature)/Foo/BarTest.php'
        // 존재 여부로 판단해서 전달한다. 모든 변경 파일에 테스트가 있으면 true.
        // false 면 evaluator 가 yellow 'untested_code_path' 발동.
        public readonly bool $hasExistingTests = true,
    ) {}
}