<?php

namespace App\Services\Llm;

use App\Services\Llm\Exceptions\LlmFatalException;
use App\Services\Llm\Exceptions\LlmRetryableException;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements LlmProviderInterface
{
    public function name(): string
    {
        return 'openai';
    }

    public function generate(LlmRequest $request): LlmResponse
    {
        $startMs = (int)(microtime(true) * 1000);
        $model   = config('services.openai.model', 'gpt-4o');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com/v1'), '/');

        try {
            $response = $this->callApi($baseUrl, $model, $request, withTemperature: true);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new LlmRetryableException('openai_timeout: ' . $e->getMessage());
        }

        $elapsedMs = (int)(microtime(true) * 1000) - $startMs;

        // temperature 미지원 모델(gpt-5.5 등): temperature 없이 재시도
        if ($response->status() === 400 && str_contains($response->body(), 'temperature')) {
            try {
                $response = $this->callApi($baseUrl, $model, $request, withTemperature: false);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new LlmRetryableException('openai_timeout: ' . $e->getMessage());
            }
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new LlmRetryableException(
                "openai_http_{$response->status()}: " . substr($response->body(), 0, 300)
            );
        }

        if ($response->clientError()) {
            throw new LlmFatalException(
                "openai_http_{$response->status()}: " . substr($response->body(), 0, 300)
            );
        }

        $content = $response->json('choices.0.message.content') ?? '';

        if (empty($content)) {
            throw new LlmRetryableException('openai_empty_response');
        }

        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LlmRetryableException('openai_invalid_json: ' . substr($content, 0, 200));
        }

        return new LlmResponse(
            content:      $content,
            providerUsed: 'openai',
            modelUsed:    $model,
            totalTokens:  $response->json('usage.total_tokens') ?? 0,
            elapsedMs:    $elapsedMs,
        );
    }

    private function callApi(string $baseUrl, string $model, LlmRequest $request, bool $withTemperature): \Illuminate\Http\Client\Response
    {
        $body = [
            'model'                 => $model,
            'max_completion_tokens' => $request->maxTokens,
            'response_format'       => ['type' => 'json_object'],
            'messages'              => [
                ['role' => 'system', 'content' => $request->systemPrompt],
                ['role' => 'user',   'content' => $request->userMessage],
            ],
        ];
        if ($withTemperature) {
            $body['temperature'] = $request->temperature;
        }

        return Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type'  => 'application/json',
            ])
            ->timeout(config('services.openai.timeout', 30))
            ->connectTimeout(10)
            ->post("{$baseUrl}/chat/completions", $body);
    }
}
