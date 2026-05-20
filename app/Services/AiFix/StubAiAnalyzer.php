<?php

namespace App\Services\AiFix;

use App\Models\SystemErrorLog;

/**
 * 실제 LLM 호출 없이 단순 휴리스틱으로 분석 결과를 만들어내는 PoC 분석기.
 *
 * 목적:
 *  - 오케스트레이터 파이프라인을 외부 API 의존 없이 테스트 가능하게 한다
 *  - 추후 ClaudeAiAnalyzer 로 교체할 때 인터페이스가 충분히 단순한지 검증
 *
 * 동작:
 *  - SystemErrorLog.file 절대경로를 base_path() 기준 상대경로로 변환해 changedFiles 로 제안
 *  - exception/message 키워드로 category 를 추정
 *  - 파일 정보가 없으면 confidence 낮추고 unsure=true
 */
final class StubAiAnalyzer implements AiAnalyzer
{
    public function analyze(SystemErrorLog $errorLog): AnalysisResult
    {
        $relative = $errorLog->file ? $this->relativize($errorLog->file) : null;
        $changedFiles = $relative ? [$relative] : [];

        $category   = $this->guessCategory($errorLog);
        $confidence = $errorLog->file ? 0.6 : 0.3;
        $unsure     = !$errorLog->file || $category === 'unknown';

        $summary = sprintf(
            '[stub] %s: %s',
            $errorLog->exception ?? 'Throwable',
            mb_substr((string) $errorLog->message, 0, 200)
        );

        return new AnalysisResult(
            category:     $category,
            confidence:   $confidence,
            changedFiles: $changedFiles,
            summary:      $summary,
            unsure:       $unsure,
        );
    }

    /** 절대 경로를 base_path() 기준 상대경로로 변환. 매칭 안 되면 그대로 반환. */
    private function relativize(string $absPath): string
    {
        $base = $this->safeBasePath();
        $norm = str_replace('\\', '/', $absPath);
        $baseN = str_replace('\\', '/', $base);
        if ($baseN !== '' && str_starts_with($norm, $baseN . '/')) {
            return substr($norm, strlen($baseN) + 1);
        }
        return $norm;
    }

    private function safeBasePath(): string
    {
        return function_exists('base_path') ? base_path() : '';
    }

    private function guessCategory(SystemErrorLog $errorLog): string
    {
        $msg = strtolower((string) $errorLog->message);
        $exc = (string) $errorLog->exception;

        if (str_contains($msg, 'null') || str_contains($msg, 'on null')) return 'null_check';
        if (str_contains($msg, 'undefined')                              ) return 'undefined_var';
        if (str_contains($msg, 'syntax')                                 ) return 'syntax_error';
        if (str_contains($exc, 'TypeError')                              ) return 'type_mismatch';
        if (str_contains($exc, 'QueryException')                         ) return 'db_query';
        if (str_contains($exc, 'ValidationException')                    ) return 'validation';
        return 'unknown';
    }
}