<?php

namespace App\Services\Llm;

class LlmRequest
{
    public function __construct(
        public readonly string $systemPrompt,
        public readonly string $userMessage,
        public readonly int $maxTokens = 2000,
        public readonly float $temperature = 0.3,
    ) {}
}
