<?php

namespace App\Services\Analysis\Llm;

class LlmClientFactory
{
    public function make(string $provider): LlmClientInterface
    {
        return match ($provider) {
            'anthropic' => new AnthropicClient(config('services.anthropic.key', '')),
            'openai'    => new OpenAiClient(config('services.openai.key', '')),
            default     => throw new \InvalidArgumentException("지원하지 않는 LLM 제공자: {$provider}"),
        };
    }
}
