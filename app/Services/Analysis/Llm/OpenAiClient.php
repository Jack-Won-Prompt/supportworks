<?php

namespace App\Services\Analysis\Llm;

use Illuminate\Support\Facades\Http;

class OpenAiClient implements LlmClientInterface
{
    private const API_URL  = 'https://api.openai.com/v1/chat/completions';
    private const MAX_TOKENS = 8000;
    private const TIMEOUT    = 300;

    public function __construct(private string $apiKey) {}

    public function complete(string $systemPrompt, string $userMessage, array $options = []): LlmResponse
    {
        $model = $options['model'] ?? config('services.openai.model', 'gpt-4o');

        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])
            ->timeout(self::TIMEOUT)
            ->post(self::API_URL, [
                'model'                 => $model,
                'max_completion_tokens' => $options['max_completion_tokens'] ?? $options['max_tokens'] ?? self::MAX_TOKENS,
                'response_format'       => ['type' => 'json_object'],
                'messages'              => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userMessage],
                ],
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException("OpenAI API 오류: {$err}");
        }

        return new LlmResponse(
            content:      $res->json('choices.0.message.content') ?? '',
            inputTokens:  $res->json('usage.prompt_tokens') ?? 0,
            outputTokens: $res->json('usage.completion_tokens') ?? 0,
            model:        $res->json('model') ?? $model,
            provider:     'openai',
        );
    }

    public function getProvider(): string
    {
        return 'openai';
    }
}
