<?php

namespace App\Services\Analysis\Llm;

class LlmResponse
{
    public function __construct(
        public readonly string $content,
        public readonly int    $inputTokens,
        public readonly int    $outputTokens,
        public readonly string $model,
        public readonly string $provider,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
