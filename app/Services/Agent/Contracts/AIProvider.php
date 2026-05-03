<?php

namespace App\Services\Agent\Contracts;

interface AIProvider
{
    /**
     * 텍스트 생성 (기본).
     *
     * @param  array<int, array{role: string, content: string|array}>  $messages
     * @param  array{max_tokens?: int, timeout?: int}  $options
     */
    public function generate(string $systemPrompt, array $messages, array $options = []): AIResponse;

    /**
     * 이미지/PDF 포함 멀티모달 생성.
     *
     * @param  array<int, array{role: string, content: string|array}>  $messages
     * @param  array<int, array{type: string, media_type: string, data: string}>  $mediaItems  base64 인코딩된 미디어
     * @param  array{max_tokens?: int, timeout?: int}  $options
     */
    public function generateWithMedia(
        string $systemPrompt,
        array  $messages,
        array  $mediaItems,
        array  $options = []
    ): AIResponse;

    /**
     * 스트리밍 생성. 청크마다 $onChunk 콜백 호출, 완료 시 AIResponse 반환.
     *
     * @param  array<int, array{role: string, content: string|array}>  $messages
     * @param  callable(string $chunk): void  $onChunk
     * @param  array{max_tokens?: int, timeout?: int}  $options
     */
    public function stream(
        string   $systemPrompt,
        array    $messages,
        callable $onChunk,
        array    $options = []
    ): AIResponse;

    /**
     * 현재 프로바이더의 모델 식별자.
     */
    public function modelId(): string;
}
