<?php

namespace App\Services\WorksBuilder\Ai;

/**
 * AI 호출 결과 DTO.
 */
class AiResult
{
    public function __construct(
        public readonly string $provider,        // 'claude' | 'openai'
        public readonly string $rawResponse,
        public readonly ?int $promptTokens,
        public readonly ?int $completionTokens,
        public readonly ?int $totalTokens,
        public readonly ?float $estimatedCostUsd,
        public readonly int $responseTimeMs,
    ) {}
}
