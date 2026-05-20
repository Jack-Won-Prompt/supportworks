<?php

namespace App\Services\Agent;

use App\Services\Agent\Contracts\AIProvider;
use App\Services\Agent\Contracts\AIResponse;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI Chat Completions API 기반 AIProvider 구현.
 *
 * AnthropicProvider와 동일한 AIProvider 계약을 따른다.
 * 미디어/스트리밍/도구는 OpenAI의 messages 포맷으로 매핑한다.
 */
class OpenAiProvider implements AIProvider
{
    private const DEFAULT_MAX_TOKENS = 16000;
    private const DEFAULT_TIMEOUT    = 240;

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $model = 'gpt-4o',
        private readonly string $baseUrl = 'https://api.openai.com/v1',
    ) {}

    /** API 호출 직전에만 호출. boot 자체는 깨지 않게 lazy 검증. */
    private function ensureApiKey(): string
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new \RuntimeException(
                'OpenAI API key not configured. Set it in AiSetting (admin) or OPENAI_API_KEY env.'
            );
        }
        return $this->apiKey;
    }

    public function generate(string $systemPrompt, array $messages, array $options = []): AIResponse
    {
        $payload  = $this->buildPayload($systemPrompt, $messages, $options, stream: false);
        $response = $this->postChat($payload, (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT));

        return new AIResponse(
            text:         (string) ($response->json('choices.0.message.content') ?? ''),
            inputTokens:  (int) ($response->json('usage.prompt_tokens') ?? 0),
            outputTokens: (int) ($response->json('usage.completion_tokens') ?? 0),
            model:        (string) ($response->json('model') ?? $this->model),
            stopReason:   (string) ($response->json('choices.0.finish_reason') ?? 'stop'),
        );
    }

    public function generateWithMedia(
        string $systemPrompt,
        array  $messages,
        array  $mediaItems,
        array  $options = []
    ): AIResponse {
        $lastUserIdx = null;
        foreach ($messages as $i => $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $lastUserIdx = $i;
            }
        }

        if ($lastUserIdx !== null) {
            $originalContent = $messages[$lastUserIdx]['content'];
            $contentBlocks   = [];

            foreach ($mediaItems as $item) {
                $contentBlocks[] = [
                    'type'      => 'image_url',
                    'image_url' => [
                        'url' => 'data:' . $item['media_type'] . ';base64,' . $item['data'],
                    ],
                ];
            }

            $contentBlocks[] = ['type' => 'text', 'text' => is_string($originalContent) ? $originalContent : ''];
            $messages[$lastUserIdx]['content'] = $contentBlocks;
        }

        return $this->generate($systemPrompt, $messages, $options);
    }

    public function stream(
        string   $systemPrompt,
        array    $messages,
        callable $onChunk,
        array    $options = []
    ): AIResponse {
        $timeout = (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT);
        $payload = $this->buildPayload($systemPrompt, $messages, $options, stream: true);

        $fullText     = '';
        $model        = $this->model;
        $stopReason   = 'stop';
        $inputTokens  = 0;
        $outputTokens = 0;

        $response = Http::withOptions(['verify' => false, 'stream' => true])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->ensureApiKey(),
                'Content-Type'  => 'application/json',
            ])
            ->timeout($timeout)
            ->post(rtrim($this->baseUrl, '/') . '/chat/completions', $payload);

        if (!$response->successful()) {
            $rawBody = '';
            $stream  = $response->getBody();
            while (!$stream->eof()) {
                $rawBody .= $stream->read(512);
            }
            $parsed = json_decode($rawBody, true);
            $msg    = $parsed['error']['message'] ?? mb_substr($rawBody, 0, 300);
            throw new \RuntimeException("OpenAI Streaming API 오류 ({$response->status()}): {$msg}");
        }

        $body   = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(1024);
            $lines   = explode("\n", $buffer);
            $buffer  = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $raw = substr($line, 6);
                if ($raw === '[DONE]') {
                    continue;
                }

                $data = json_decode($raw, true);
                if (!is_array($data)) {
                    continue;
                }

                $model      = $data['model'] ?? $model;
                $delta      = $data['choices'][0]['delta']['content'] ?? '';
                $finish     = $data['choices'][0]['finish_reason'] ?? null;

                if ($delta !== '') {
                    $fullText .= $delta;
                    ($onChunk)($delta);
                }

                if ($finish) {
                    $stopReason = $finish;
                }

                if (isset($data['usage'])) {
                    $inputTokens  = (int) ($data['usage']['prompt_tokens'] ?? $inputTokens);
                    $outputTokens = (int) ($data['usage']['completion_tokens'] ?? $outputTokens);
                }
            }
        }

        return new AIResponse(
            text:         $fullText,
            inputTokens:  $inputTokens,
            outputTokens: $outputTokens,
            model:        $model,
            stopReason:   $stopReason,
        );
    }

    public function modelId(): string
    {
        return $this->model;
    }

    private function buildPayload(string $systemPrompt, array $messages, array $options, bool $stream): array
    {
        $payload = [
            'model'    => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages,
            ),
            'max_tokens' => (int) ($options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS),
        ];

        if ($stream) {
            $payload['stream'] = true;
            $payload['stream_options'] = ['include_usage' => true];
        }

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        return $payload;
    }

    private function postChat(array $payload, int $timeout): \Illuminate\Http\Client\Response
    {
        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->ensureApiKey(),
                'Content-Type'  => 'application/json',
            ])
            ->timeout($timeout)
            ->post(rtrim($this->baseUrl, '/') . '/chat/completions', $payload);

        if (!$res->successful()) {
            throw \App\Support\AiError::wrap('OpenAI', __METHOD__, $res);
        }

        return $res;
    }
}
