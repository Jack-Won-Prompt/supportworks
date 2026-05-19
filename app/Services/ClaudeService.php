<?php

namespace App\Services;

use App\Services\Concerns\ParsesAiResponse;
use Illuminate\Support\Facades\Http;

class ClaudeService
{
    use ParsesAiResponse;

    private const API      = 'https://api.anthropic.com/v1/messages';
    private const MODEL    = 'claude-sonnet-4-6';
    private const MAX_TOKENS_CHAT      = 8000;
    private const MAX_TOKENS_BUILDER   = 16000;
    private const MAX_TOKENS_REFINE    = 2000;
    private const MAX_TOKENS_TRANSLATE = 500;
    private const TIMEOUT_CHAT         = 120;
    private const TIMEOUT_BUILDER      = 240;
    private const TIMEOUT_REFINE       = 60;
    private const TIMEOUT_TRANSLATE    = 20;

    public function __construct(private string $apiKey) {}

    public function chat(array $messages, ?string $figmaContext = null, ?string $systemOverride = null): array
    {
        if ($systemOverride) {
            $system = $figmaContext ? $systemOverride . "\n\n## Figma 파일 구조\n" . $figmaContext : $systemOverride;
        } else {
            $system = $figmaContext
                ? AiPrompts::figmaSystem() . "\n\n## Figma 파일 구조\n" . $figmaContext
                : AiPrompts::system();
        }

        $res = $this->request(self::MAX_TOKENS_CHAT, self::TIMEOUT_CHAT, $system, $messages);

        return $this->parseResponse($res->json('content.0.text') ?? '');
    }

    public function chatRaw(array $messages, string $systemPrompt): string
    {
        $res = $this->request(self::MAX_TOKENS_CHAT, self::TIMEOUT_CHAT, $systemPrompt, $messages);

        return $res->json('content.0.text') ?? '';
    }

    /**
     * 빠른 정제·요약용 — Haiku 모델 사용, 토큰·타임아웃 축소.
     * Sonnet 4.6 대비 응답 속도 약 2~3배.
     */
    public function chatRawFast(array $messages, string $systemPrompt): string
    {
        $res = $this->requestWithModel(
            model: 'claude-haiku-4-5-20251001',
            maxTokens: 2000,
            timeout: 40,
            system: $systemPrompt,
            messages: $messages,
        );

        return $res->json('content.0.text') ?? '';
    }

    public function chatRawTranslate(array $messages, string $systemPrompt): string
    {
        $res = $this->request(self::MAX_TOKENS_TRANSLATE, self::TIMEOUT_TRANSLATE, $systemPrompt, $messages);

        return $res->json('content.0.text') ?? '';
    }

    public function chatRawLarge(array $messages, string $systemPrompt): string
    {
        $res = $this->request(self::MAX_TOKENS_BUILDER, self::TIMEOUT_BUILDER, $systemPrompt, $messages);

        return $res->json('content.0.text') ?? '';
    }

    public function streamRaw(string $systemPrompt, array $messages, callable $onChunk, int $maxTokens = 4000): void
    {
        $response = Http::withOptions(['verify' => false, 'stream' => true])
            ->withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout(90)
            ->post(self::API, [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => $messages,
                'stream'     => true,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Claude stream API 오류: ' . $response->status());
        }

        $body   = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(512);
            $lines   = explode("\n", $buffer);
            $buffer  = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data: ')) continue;
                $data = json_decode(substr($line, 6), true);
                if (!$data) continue;
                if (($data['type'] ?? '') === 'content_block_delta') {
                    $chunk = $data['delta']['text'] ?? '';
                    if ($chunk !== '') $onChunk($chunk);
                }
            }
        }
    }

    public function refinePrompt(string $userInput, ?array $existing = null): array
    {
        $content = $existing
            ? "기존 프롬프트:\n" . json_encode($existing, JSON_UNESCAPED_UNICODE) . "\n\n새 요청:\n" . $userInput
            : $userInput;

        $res = $this->request(
            self::MAX_TOKENS_REFINE,
            self::TIMEOUT_REFINE,
            AiPrompts::refineSystem(),
            [['role' => 'user', 'content' => $content]]
        );

        return $this->parseRefineResponse($res->json('content.0.text') ?? '');
    }

    private function request(int $maxTokens, int $timeout, string $system, array $messages): \Illuminate\Http\Client\Response
    {
        return $this->requestWithModel(self::MODEL, $maxTokens, $timeout, $system, $messages);
    }

    private function requestWithModel(string $model, int $maxTokens, int $timeout, string $system, array $messages): \Illuminate\Http\Client\Response
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout($timeout)
            ->post(self::API, [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'system'     => $system,
                'messages'   => $messages,
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException(\App\Support\AiError::friendly("Claude API 오류: {$err}"));
        }

        return $res;
    }
}
