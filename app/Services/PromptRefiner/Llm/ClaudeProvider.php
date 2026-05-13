<?php

namespace App\Services\PromptRefiner\Llm;

use App\Services\PromptRefiner\Llm\Exceptions\LlmFatalException;
use App\Services\PromptRefiner\Llm\Exceptions\LlmRetryableException;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements LlmProviderInterface
{
    private const API = 'https://api.anthropic.com/v1/messages';

    public function name(): string
    {
        return 'claude';
    }

    public function generate(LlmRequest $request): LlmResponse
    {
        $startMs = (int)(microtime(true) * 1000);
        $model   = config('services.anthropic.model', 'claude-opus-4-7');

        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'x-api-key'         => config('services.anthropic.key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->timeout(config('services.anthropic.timeout', 30))
                ->connectTimeout(10)
                ->post(self::API, [
                    'model'       => $model,
                    'max_tokens'  => $request->maxTokens,
                    'temperature' => $request->temperature,
                    'system'      => $request->systemPrompt,
                    'messages'    => [
                        ['role' => 'user', 'content' => $request->userMessage],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new LlmRetryableException('claude_timeout: ' . $e->getMessage());
        }

        $elapsedMs = (int)(microtime(true) * 1000) - $startMs;

        if ($response->serverError() || $response->status() === 429) {
            throw new LlmRetryableException(
                "claude_http_{$response->status()}: " . substr($response->body(), 0, 300)
            );
        }

        if ($response->clientError()) {
            // 401(인증 실패)만 Fatal — 나머지 4xx(크레딧 부족·한도 초과 등)는 폴백 가능
            if ($response->status() === 401) {
                throw new LlmFatalException(
                    "claude_http_{$response->status()}: " . substr($response->body(), 0, 300)
                );
            }
            throw new LlmRetryableException(
                "claude_http_{$response->status()}: " . substr($response->body(), 0, 300)
            );
        }

        $text = $response->json('content.0.text') ?? '';

        // Strip markdown code block wrapper if present
        if (preg_match('/```json\s*([\s\S]+?)\s*```/i', $text, $m)) {
            $text = $m[1];
        }

        if (empty($text)) {
            throw new LlmRetryableException('claude_empty_response');
        }

        json_decode($text);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LlmRetryableException('claude_invalid_json: ' . substr($text, 0, 200));
        }

        $usage = $response->json('usage') ?? ['input_tokens' => 0, 'output_tokens' => 0];

        return new LlmResponse(
            content:      $text,
            providerUsed: 'claude',
            modelUsed:    $model,
            totalTokens:  ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0),
            elapsedMs:    $elapsedMs,
        );
    }
}
