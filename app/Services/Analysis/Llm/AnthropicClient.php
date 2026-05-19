<?php

namespace App\Services\Analysis\Llm;

use Illuminate\Support\Facades\Http;

class AnthropicClient implements LlmClientInterface
{
    private const API_URL    = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MAX_TOKENS = 8000;
    private const TIMEOUT    = 300;

    public function __construct(private string $apiKey) {}

    public function complete(string $systemPrompt, string $userMessage, array $options = []): LlmResponse
    {
        $model = $options['model'] ?? 'claude-sonnet-4-6';

        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ])
            ->timeout(self::TIMEOUT)
            ->post(self::API_URL, [
                'model'      => $model,
                'max_tokens' => $options['max_tokens'] ?? self::MAX_TOKENS,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException(\App\Support\AiError::friendly("Anthropic API 오류: {$err}"));
        }

        return new LlmResponse(
            content:      $res->json('content.0.text') ?? '',
            inputTokens:  $res->json('usage.input_tokens') ?? 0,
            outputTokens: $res->json('usage.output_tokens') ?? 0,
            model:        $res->json('model') ?? $model,
            provider:     'anthropic',
        );
    }

    public function getProvider(): string
    {
        return 'anthropic';
    }
}
