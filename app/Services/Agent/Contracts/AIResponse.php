<?php

namespace App\Services\Agent\Contracts;

readonly class AIResponse
{
    public function __construct(
        public string $text,
        public int    $inputTokens,
        public int    $outputTokens,
        public string $model,
        public string $stopReason = 'end_turn',
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
