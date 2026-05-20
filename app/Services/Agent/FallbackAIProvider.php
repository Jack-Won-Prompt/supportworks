<?php

namespace App\Services\Agent;

use App\Services\Agent\Contracts\AIProvider;
use App\Services\Agent\Contracts\AIResponse;
use Illuminate\Support\Facades\Log;

/**
 * 두 AIProvider 를 wrap. primary 실패 (예외 throw) 시 secondary 로 자동 재시도.
 *
 * 운영 예시: new FallbackAIProvider(AnthropicProvider, OpenAiProvider)
 *   - 평소: Claude 사용
 *   - Claude 키 없거나 API 장애: OpenAI 로 fallback
 *
 * 주의:
 *   - secondary 도 실패하면 그 예외 그대로 throw — 호출자가 처리해야.
 *   - fallback 발동은 Log::warning 으로 남김 (운영 관측용).
 *   - stream() 의 fallback 처리는 까다로움 (이미 일부 청크 전달된 후 실패 시).
 *     안전을 위해 stream() 도 try-catch 하지만 primary 가 청크 전달 후 fail 하면
 *     호출자는 secondary 결과만 받게 됨 (incomplete 응답).
 */
class FallbackAIProvider implements AIProvider
{
    public function __construct(
        private readonly AIProvider $primary,
        private readonly AIProvider $secondary,
    ) {}

    public function generate(string $systemPrompt, array $messages, array $options = []): AIResponse
    {
        return $this->tryWithFallback(
            method: 'generate',
            primaryCall:   fn () => $this->primary->generate($systemPrompt, $messages, $options),
            secondaryCall: fn () => $this->secondary->generate($systemPrompt, $messages, $options),
        );
    }

    public function generateWithMedia(
        string $systemPrompt,
        array  $messages,
        array  $mediaItems,
        array  $options = []
    ): AIResponse {
        return $this->tryWithFallback(
            method: 'generateWithMedia',
            primaryCall:   fn () => $this->primary->generateWithMedia($systemPrompt, $messages, $mediaItems, $options),
            secondaryCall: fn () => $this->secondary->generateWithMedia($systemPrompt, $messages, $mediaItems, $options),
        );
    }

    public function stream(
        string   $systemPrompt,
        array    $messages,
        callable $onChunk,
        array    $options = []
    ): AIResponse {
        return $this->tryWithFallback(
            method: 'stream',
            primaryCall:   fn () => $this->primary->stream($systemPrompt, $messages, $onChunk, $options),
            secondaryCall: fn () => $this->secondary->stream($systemPrompt, $messages, $onChunk, $options),
        );
    }

    public function modelId(): string
    {
        // 외부에서 어떤 모델인지 표시하는 용도. primary 우선, 실패 시 secondary.
        try {
            return $this->primary->modelId();
        } catch (\Throwable) {
            return $this->secondary->modelId();
        }
    }

    /**
     * primary 시도 → 예외 시 Log::warning + secondary 재시도.
     * 두 호출 모두 closure 로 받아서 메서드별 시그니처 차이를 흡수.
     *
     * @template T
     * @param  callable():T  $primaryCall
     * @param  callable():T  $secondaryCall
     * @return T
     */
    private function tryWithFallback(string $method, callable $primaryCall, callable $secondaryCall): mixed
    {
        try {
            return $primaryCall();
        } catch (\Throwable $e) {
            Log::warning(sprintf(
                '[FallbackAIProvider::%s] primary (%s) failed: %s — falling back to secondary (%s)',
                $method,
                $this->safeName($this->primary),
                $e->getMessage(),
                $this->safeName($this->secondary),
            ));
            return $secondaryCall();
        }
    }

    private function safeName(AIProvider $p): string
    {
        return (new \ReflectionClass($p))->getShortName();
    }
}
