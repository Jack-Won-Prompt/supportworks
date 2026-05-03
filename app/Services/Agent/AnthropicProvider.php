<?php

namespace App\Services\Agent;

use App\Services\Agent\Contracts\AIProvider;
use App\Services\Agent\Contracts\AIResponse;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AIProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-sonnet-4-6';

    private const DEFAULT_MAX_TOKENS = 16000;
    private const DEFAULT_TIMEOUT    = 240;

    public function __construct(private readonly string $apiKey) {}

    public function generate(string $systemPrompt, array $messages, array $options = []): AIResponse
    {
        $res = $this->request($systemPrompt, $messages, $options);

        return new AIResponse(
            text:         $res->json('content.0.text') ?? '',
            inputTokens:  $res->json('usage.input_tokens') ?? 0,
            outputTokens: $res->json('usage.output_tokens') ?? 0,
            model:        $res->json('model') ?? self::MODEL,
            stopReason:   $res->json('stop_reason') ?? 'end_turn',
        );
    }

    public function generateWithMedia(
        string $systemPrompt,
        array  $messages,
        array  $mediaItems,
        array  $options = []
    ): AIResponse {
        // 마지막 user 메시지에 미디어 첨부
        $lastUserIdx = null;
        foreach ($messages as $i => $msg) {
            if ($msg['role'] === 'user') {
                $lastUserIdx = $i;
            }
        }

        if ($lastUserIdx !== null) {
            $originalContent = $messages[$lastUserIdx]['content'];
            $contentBlocks   = [];

            foreach ($mediaItems as $item) {
                $contentBlocks[] = [
                    'type'   => 'image',
                    'source' => [
                        'type'       => 'base64',
                        'media_type' => $item['media_type'],
                        'data'       => $item['data'],
                    ],
                ];
            }

            $contentBlocks[]               = ['type' => 'text', 'text' => $originalContent];
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
        $maxTokens = $options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS;
        $timeout   = $options['timeout'] ?? self::DEFAULT_TIMEOUT;

        $fullText     = '';
        $inputTokens  = 0;
        $outputTokens = 0;
        $model        = self::MODEL;
        $stopReason   = 'end_turn';

        $response = Http::withOptions(['verify' => false, 'stream' => true])
            ->withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout($timeout)
            ->post(self::API_URL, [
                'model'      => self::MODEL,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => $messages,
                'stream'     => true,
            ]);

        $body = $response->getBody();
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

                $data = json_decode(substr($line, 6), true);
                if (!$data) {
                    continue;
                }

                match ($data['type'] ?? '') {
                    'message_start'   => ($inputTokens = $data['message']['usage']['input_tokens'] ?? 0) || ($model = $data['message']['model'] ?? self::MODEL),
                    'content_block_delta' => (function () use (&$fullText, &$onChunk, $data) {
                        $chunk = $data['delta']['text'] ?? '';
                        if ($chunk !== '') {
                            $fullText .= $chunk;
                            ($onChunk)($chunk);
                        }
                    })(),
                    'message_delta'   => ($outputTokens = $data['usage']['output_tokens'] ?? 0) || ($stopReason = $data['delta']['stop_reason'] ?? 'end_turn'),
                    default           => null,
                };
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
        return self::MODEL;
    }

    private function request(string $systemPrompt, array $messages, array $options): \Illuminate\Http\Client\Response
    {
        $maxTokens = $options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS;
        $timeout   = $options['timeout'] ?? self::DEFAULT_TIMEOUT;

        $res = Http::withOptions(['verify' => false])
            ->withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout($timeout)
            ->post(self::API_URL, [
                'model'      => self::MODEL,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => $messages,
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            throw new \RuntimeException("Anthropic API 오류: {$err}");
        }

        return $res;
    }
}
