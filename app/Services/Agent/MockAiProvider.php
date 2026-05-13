<?php

namespace App\Services\Agent;

use App\Services\Agent\Contracts\AIProvider;
use App\Services\Agent\Contracts\AIResponse;

/**
 * API 키가 설정되지 않았을 때 사용하는 placeholder 응답기.
 *
 * 실제 API 호출 없이 결정적인 mock 텍스트를 반환해 UI/플로우를 검증할 수 있게 한다.
 * config('ai-agent.sessions.mock_when_unconfigured')가 true일 때만 활성화.
 */
class MockAiProvider implements AIProvider
{
    public function __construct(private readonly string $label = 'mock-claude') {}

    public function generate(string $systemPrompt, array $messages, array $options = []): AIResponse
    {
        $text = $this->buildMockText($systemPrompt, $messages);

        return new AIResponse(
            text:         $text,
            inputTokens:  $this->estimateTokens($systemPrompt) + $this->estimateMessagesTokens($messages),
            outputTokens: $this->estimateTokens($text),
            model:        $this->label,
            stopReason:   'end_turn',
        );
    }

    public function generateWithMedia(
        string $systemPrompt,
        array  $messages,
        array  $mediaItems,
        array  $options = []
    ): AIResponse {
        return $this->generate(
            $systemPrompt,
            $messages,
            $options,
        );
    }

    public function stream(
        string   $systemPrompt,
        array    $messages,
        callable $onChunk,
        array    $options = []
    ): AIResponse {
        $text = $this->buildMockText($systemPrompt, $messages);

        foreach (mb_str_split($text, 32) as $chunk) {
            ($onChunk)($chunk);
        }

        return new AIResponse(
            text:         $text,
            inputTokens:  $this->estimateTokens($systemPrompt) + $this->estimateMessagesTokens($messages),
            outputTokens: $this->estimateTokens($text),
            model:        $this->label,
            stopReason:   'end_turn',
        );
    }

    public function modelId(): string
    {
        return $this->label;
    }

    private function buildMockText(string $systemPrompt, array $messages): string
    {
        $lastUser = '';
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $content = $msg['content'] ?? '';
                $lastUser = is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE);
            }
        }

        return json_encode([
            'mock'    => true,
            'note'    => 'AI Agent mock provider — API 키 미설정으로 placeholder 응답을 반환합니다.',
            'system'  => mb_substr($systemPrompt, 0, 120),
            'echo'    => mb_substr($lastUser, 0, 200),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    private function estimateMessagesTokens(array $messages): int
    {
        $sum = 0;
        foreach ($messages as $msg) {
            $c = $msg['content'] ?? '';
            $sum += $this->estimateTokens(is_string($c) ? $c : json_encode($c, JSON_UNESCAPED_UNICODE));
        }
        return $sum;
    }
}
