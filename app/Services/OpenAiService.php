<?php

namespace App\Services;

use App\Services\Concerns\ParsesAiResponse;
use Illuminate\Support\Facades\Http;

class OpenAiService
{
    use ParsesAiResponse;

    private const API           = 'https://api.openai.com/v1/chat/completions';
    private const WHISPER_API   = 'https://api.openai.com/v1/audio/transcriptions';
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
        // tools(function-calling) 호출 시 일부 모델(gpt-5.5)이 500 server_error를 내므로 별도 모델 사용
        $toolsModel = config('services.openai.tools_model', 'gpt-4o');

        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout(120)
            ->post(self::API, [
                'model'    => $toolsModel,
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
            throw \App\Support\AiError::wrap('OpenAI', __METHOD__, $res);
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
            throw \App\Support\AiError::wrap('OpenAI', __METHOD__, $res);
        }

        return $res;
    }

    /**
     * Whisper STT - 오디오 파일을 텍스트로 변환
     */
    public function transcribeAudio(string $absolutePath, string $language = 'ko', bool $verbose = false): array
    {
        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("녹음 파일을 찾을 수 없습니다: {$absolutePath}");
        }

        $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(600)
            ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
            ->asMultipart()
            ->post(self::WHISPER_API, [
                'model'           => 'whisper-1',
                'language'        => $language,
                'response_format' => $verbose ? 'verbose_json' : 'json',
                'temperature'     => '0',
            ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Whisper API 실패: ' . $res->status() . ' ' . $res->body());
        }

        $body = $res->json();
        return [
            'text'     => $body['text'] ?? '',
            'segments' => $body['segments'] ?? null,
        ];
    }

    /**
     * 녹취록을 정리된 회의록(마크다운)으로 변환
     */
    public function generateMeetingMinutes(string $transcript, ?string $title = null): string
    {
        $system = <<<PROMPT
당신은 한국어 비즈니스 회의록 작성 전문가입니다.
아래 녹취록(STT 결과)을 바탕으로 깔끔한 회의록을 작성하세요.

요구 형식 (마크다운):
## 회의 요약
- 핵심 요지를 3~5개 불릿으로

## 주요 논의 사항
1. 안건/주제별로 정리

## 결정 사항
- 명확하게 결정된 내용들

## 액션 아이템
- [ ] 누가 / 무엇을 / 언제까지

## 다음 회의/후속 조치
- (있을 경우)

규칙:
- 녹취록에 명시되지 않은 내용은 추측하지 말 것
- 화자가 식별되지 않으면 일반적 서술로
- 잡담/필러는 제거하고 핵심만
- 한국어 존댓말 사용
PROMPT;

        $userMsg = ($title ? "회의 제목: {$title}\n\n" : '') . "녹취록:\n{$transcript}";

        return $this->chatRawLarge(
            [['role' => 'user', 'content' => $userMsg]],
            $system,
        );
    }
}
