<?php

namespace App\Services\WorksBuilder\Ai\Providers;

use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiResult;
use App\Services\WorksBuilder\Ai\Security\ApiKeyResolver;
use Illuminate\Support\Facades\Http;

class OpenAiApiClient implements AiProviderInterface
{
    private const TIMEOUT = 180;
    private const COST_INPUT_PER_M  = 2.50;
    private const COST_OUTPUT_PER_M = 10.00;

    public function __construct(private ApiKeyResolver $keys) {}

    public function name(): string
    {
        return 'openai';
    }

    public function generate(string $systemPrompt, string $userPrompt): AiResult
    {
        $key = $this->keys->openai();
        if (!$key) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_HTTP_4XX,
                'OpenAI API 키가 설정되지 않았습니다.',
                fatal: true,
            );
        }

        $model = config('services.openai.model', 'gpt-4o');
        $base  = rtrim(config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $start = (int)(microtime(true) * 1000);

        try {
            $res = Http::withOptions([
                    'verify' => false,
                    'curl'   => [
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_SSL_VERIFYHOST => 0,
                    ],
                ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ])
                ->timeout(self::TIMEOUT)
                ->connectTimeout(30)
                ->post("{$base}/chat/completions", [
                    'model'                 => $model,
                    'max_completion_tokens' => 16000,
                    'messages'              => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_TIMEOUT,
                'openai_timeout: ' . $e->getMessage(),
            );
        }

        $elapsed = (int)(microtime(true) * 1000) - $start;

        if ($res->status() === 429) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_RATE_LIMIT,
                'openai_429: ' . substr($res->body(), 0, 300),
            );
        }
        if ($res->serverError()) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_HTTP_5XX,
                "openai_http_{$res->status()}: " . substr($res->body(), 0, 300),
            );
        }
        if ($res->clientError()) {
            $fatal = in_array($res->status(), [401, 403], true);
            throw new AiAttemptException(
                AiAttemptException::STATUS_HTTP_4XX,
                "openai_http_{$res->status()}: " . substr($res->body(), 0, 300),
                fatal: $fatal,
            );
        }

        $text = $res->json('choices.0.message.content') ?? '';
        if ($text === '') {
            throw new AiAttemptException(
                AiAttemptException::STATUS_PARSE_ERROR,
                'openai_empty_response',
            );
        }

        $finish = $res->json('choices.0.finish_reason');
        if ($finish === 'content_filter') {
            throw new AiAttemptException(
                AiAttemptException::STATUS_CONTENT_FILTER,
                'openai_content_filtered',
            );
        }

        $usage  = $res->json('usage') ?? [];
        $inTok  = (int) ($usage['prompt_tokens']     ?? 0);
        $outTok = (int) ($usage['completion_tokens'] ?? 0);
        $cost   = ($inTok / 1_000_000) * self::COST_INPUT_PER_M
                + ($outTok / 1_000_000) * self::COST_OUTPUT_PER_M;

        return new AiResult(
            provider:         'openai',
            rawResponse:      $text,
            promptTokens:     $inTok,
            completionTokens: $outTok,
            totalTokens:      $inTok + $outTok,
            estimatedCostUsd: round($cost, 4),
            responseTimeMs:   $elapsed,
        );
    }
}
