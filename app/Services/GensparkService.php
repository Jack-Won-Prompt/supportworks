<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GensparkService
{
    public function __construct(
        private string $apiKey,
        private string $endpoint,
        private string $model,
    ) {}

    /**
     * 문서/텍스트 생성용 (chatRaw 호환)
     */
    public function chatRaw(array $messages, string $systemPrompt): string
    {
        $payload = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        $res = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post($this->endpoint, [
                'model'      => $this->model,
                'max_tokens' => 8000,
                'messages'   => $payload,
            ]);

        if (!$res->successful()) {
            $err = $res->json('error.message') ?? $res->body();
            Log::warning('[GensparkService] API 오류', [
                'status' => $res->status(),
                'error'  => $err,
            ]);
            throw new \RuntimeException("Genspark API 오류 ({$res->status()}): {$err}");
        }

        $content = $res->json('choices.0.message.content');
        if ($content === null) {
            throw new \RuntimeException('Genspark API 응답에서 content를 찾을 수 없습니다.');
        }

        return $content;
    }
}
