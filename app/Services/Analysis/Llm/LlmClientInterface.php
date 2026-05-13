<?php

namespace App\Services\Analysis\Llm;

interface LlmClientInterface
{
    public function complete(string $systemPrompt, string $userMessage, array $options = []): LlmResponse;

    public function getProvider(): string;
}
