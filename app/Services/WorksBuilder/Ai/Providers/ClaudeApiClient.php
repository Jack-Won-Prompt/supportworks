<?php

namespace App\Services\WorksBuilder\Ai\Providers;

use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiResult;
use App\Services\WorksBuilder\Ai\Security\ApiKeyResolver;
use Illuminate\Support\Facades\Http;

class ClaudeApiClient implements AiProviderInterface
{
    private const API     = 'https://api.anthropic.com/v1/messages';
    private const TIMEOUT = 120;
    private const COST_INPUT_PER_M  = 3.00;
    private const COST_OUTPUT_PER_M = 15.00;

    public function __construct(private ApiKeyResolver $keys) {}

    public function name(): string
    {
        return 'claude';
    }

    public function generate(string $systemPrompt, string $userPrompt): AiResult
    {
        $key = $this->keys->claude();
        if (!$key) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_HTTP_4XX,
                'Claude API 키가 설정되지 않았습니다.',
                fatal: true,
            );
        }

        $model = config('services.anthropic.model', 'claude-opus-4-7');
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
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->timeout(self::TIMEOUT)
                ->connectTimeout(30)
                ->post(self::API, [
                    'model'      => $model,
                    'max_tokens' => 8000,
                    'system'     => $systemPrompt,
                    'messages'   => [['role' => 'user', 'content' => $userPrompt]],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_TIMEOUT,
                'claude_timeout: ' . $e->getMessage(),
            );
        }

        $elapsed = (int)(microtime(true) * 1000) - $start;

        if ($res->status() === 429) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_RATE_LIMIT,
                'claude_429: ' . substr($res->body(), 0, 300),
            );
        }
        if ($res->serverError()) {
            throw new AiAttemptException(
                AiAttemptException::STATUS_HTTP_5XX,
                "claude_http_{$res->status()}: " . substr($res->body(), 0, 300),
            );
        }
        if ($res->clientError()) {
            $fatal = in_array($res->status(), [401, 403], true);
            throw new AiAttemptException(
                AiAttemptException::STATUS_HTTP_4XX,
                "claude_http_{$res->status()}: " . substr($res->body(), 0, 300),
                fatal: $fatal,
            );
        }

        $text = $res->json('content.0.text') ?? '';
        if ($text === '') {
            throw new AiAttemptException(
                AiAttemptException::STATUS_PARSE_ERROR,
                'claude_empty_response',
            );
        }

        $stop = $res->json('stop_reason');
        if ($stop === 'content_filter' || $stop === 'refusal') {
            throw new AiAttemptException(
                AiAttemptException::STATUS_CONTENT_FILTER,
                'claude_content_filtered',
            );
        }

        $usage  = $res->json('usage') ?? [];
        $inTok  = (int) ($usage['input_tokens']  ?? 0);
        $outTok = (int) ($usage['output_tokens'] ?? 0);
        $cost   = ($inTok / 1_000_000) * self::COST_INPUT_PER_M
                + ($outTok / 1_000_000) * self::COST_OUTPUT_PER_M;

        return new AiResult(
            provider:         'claude',
            rawResponse:      $text,
            promptTokens:     $inTok,
            completionTokens: $outTok,
            totalTokens:      $inTok + $outTok,
            estimatedCostUsd: round($cost, 4),
            responseTimeMs:   $elapsed,
        );
    }
}
