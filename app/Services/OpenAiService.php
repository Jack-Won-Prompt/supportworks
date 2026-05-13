<?php

namespace App\Services;

use App\Services\Concerns\ParsesAiResponse;
use Illuminate\Support\Facades\Http;

class OpenAiService
{
    use ParsesAiResponse;

    private const API      = 'https://api.openai.com/v1/chat/completions';
    private string $model;
    private const MAX_TOKENS_CHAT      = 8000;
    private const MAX_TOKENS_BUILDER   = 16000;
    private const MAX_TOKENS_REFINE    = 2000;
    private const MAX_TOKENS_TRANSLATE = 500;
    private const TIMEOUT_CHAT         = 120;
    private const TIMEOUT_BUILDER      = 240;
    private const TIMEOUT_REFINE       = 60;
    private const TIMEOUT_TRANSLATE    = 20;

    public function __construct(private string $apiKey)
    {
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    public function chat(array $messages, ?string $figmaContext = null, ?string $systemOverride = null): array
    {
        if ($systemOverride) {
            $system = $figmaContext ? $systemOverride . "\n\n## Figma 파일 구조\n" . $figmaContext : $systemOverride;
        } else {
            $system = $figmaContext
                ? AiPrompts::figmaSystem() . "\n\n## Figma 파일 구조\n" . $figmaContext
                : AiPrompts::system();
        }

        $res = $this->request(
            self::MAX_TOKENS_CHAT,
            self::TIMEOUT_CHAT,
            $system,
            $messages
        );

        return $this->parseResponse($res->json('choices.0.message.content') ?? '');
    }

    public function chatRaw(array $messages, string $systemPrompt): string
    {
        $res = $this->request(self::MAX_TOKENS_CHAT, self::TIMEOUT_CHAT, $systemPrompt, $messages);

        return $res->json('choices.0.message.content') ?? '';
    }

    /**
     * 빠른 정제·요약용 — gpt-4o-mini 모델, 토큰·타임아웃 축소.
     */
    public function chatRawFast(array $messages, string $systemPrompt): string
    {
        $original = $this->model;
        $this->model = 'gpt-4o-mini';
        try {
            $res = $this->request(2000, 40, $systemPrompt, $messages);
            return $res->json('choices.0.message.content') ?? '';
        } finally {
            $this->model = $original;
        }
    }

    public function chatRawTranslate(array $messages, string $systemPrompt): string
    {
        $res = $this->request(self::MAX_TOKENS_TRANSLATE, self::TIMEOUT_TRANSLATE, $systemPrompt, $messages);

        return $res->json('choices.0.message.content') ?? '';
    }

    public function chatRawLarge(array $messages, string $systemPrompt): string
    {
        $res = $this->request(self::MAX_TOKENS_BUILDER, self::TIMEOUT_BUILDER, $systemPrompt, $messages);

        return $res->json('choices.0.message.content') ?? '';
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

        return $this->parseRefineResponse($res->json('choices.0.message.content') ?? '');
    }

    /**
     * Function calling으로 구조화된 필드 초안을 반환합니다.
     *
     * @param  array{type: string, properties: array, required: array}  $fieldSchema  JSON Schema
     * @return array<string, string>
     */
    public function generateDraftFields(string $systemPrompt, string $userPrompt, array $fieldSchema): array
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(120)
            ->post(self::API, [
                'model'    => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'tools' => [[
                    'type'     => 'function',
                    'function' => [
                        'name'        => 'fill_draft_fields',
                        'description' => '산출물 단계 초안을 필드별로 작성합니다.',
                        'parameters'  => $fieldSchema,
                    ],
                ]],
                'tool_choice' => ['type' => 'function', 'function' => ['name' => 'fill_draft_fields']],
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException("OpenAI API 오류: {$err}");
        }

        $args = $res->json('choices.0.message.tool_calls.0.function.arguments');
        if (!$args) {
            throw new \RuntimeException('OpenAI function call 응답이 없습니다.');
        }

        $parsed = json_decode($args, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException('OpenAI function call JSON 파싱 실패: ' . $args);
        }

        return $parsed;
    }

    private function request(int $maxTokens, int $timeout, string $system, array $messages): \Illuminate\Http\Client\Response
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout($timeout)
            ->post(self::API, [
                'model'                 => $this->model,
                'max_completion_tokens' => $maxTokens,
                'messages'   => array_merge(
                    [['role' => 'system', 'content' => $system]],
                    $messages
                ),
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException("웍스 API 오류: {$err}");
        }

        return $res;
    }
}
