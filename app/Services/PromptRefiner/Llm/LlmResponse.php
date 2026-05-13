<?php

namespace App\Services\PromptRefiner\Llm;

class LlmResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $providerUsed,
        public readonly string $modelUsed,
        public readonly int $totalTokens,
        public readonly int $elapsedMs,
        public readonly ?string $fallbackReason = null,
    ) {}
}
