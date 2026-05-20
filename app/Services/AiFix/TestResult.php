<?php

namespace App\Services\AiFix;

/**
 * 테스트 실행 결과 요약. AiFixJob.test_result JSON 에 그대로 저장.
 */
final class TestResult
{
    public function __construct(
        public readonly bool   $passed,
        public readonly int    $testsRun       = 0,
        public readonly int    $assertions     = 0,
        public readonly int    $failures       = 0,
        public readonly int    $errors         = 0,
        public readonly int    $coverageDelta  = 0,    // 새로 커버된 라인 수
        public readonly string $output         = '',   // 콘솔 출력 (필요 시 잘림)
    ) {}

    public function toArray(): array
    {
        return [
            'passed'         => $this->passed,
            'tests_run'      => $this->testsRun,
            'assertions'     => $this->assertions,
            'failures'       => $this->failures,
            'errors'         => $this->errors,
            'coverage_delta' => $this->coverageDelta,
            'output'         => mb_substr($this->output, 0, 4000),
        ];
    }
}