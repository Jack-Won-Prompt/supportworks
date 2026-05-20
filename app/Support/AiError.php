<?php

namespace App\Support;

use App\Models\SystemErrorLog;
use Illuminate\Http\Client\Response;

class AiError
{
    /**
     * 원본 오류를 관리자 시스템 에러 페이지에 기록한다.
     * 사용자에게는 friendly() 메시지만 보여줘도, 관리자는 정확한 원문을 봐야 진단 가능.
     *
     * @param  string                    $provider     'OpenAI' / 'Anthropic' / 'Claude' / 'Manus'
     * @param  string                    $sourceLabel  발생 위치 식별자 (예: __METHOD__)
     * @param  Response|string|array     $payload      HTTP\Client Response | 원본 문자열 | 컨텍스트 배열
     */
    public static function record(string $provider, string $sourceLabel, Response|string|array $payload): void
    {
        $rawMessage = '';
        $context    = ['source' => $sourceLabel, 'provider' => $provider];

        if ($payload instanceof Response) {
            $rawMessage = (string) ($payload->json('error.message')
                ?? $payload->json('error.type')
                ?? $payload->body());
            $context['http_status']     = $payload->status();
            $context['raw_body']        = mb_substr((string) $payload->body(), 0, 4000);
            $context['error_type']      = $payload->json('error.type');
            $context['error_code']      = $payload->json('error.code');
        } elseif (is_string($payload)) {
            $rawMessage = $payload;
        } else {
            $rawMessage = $payload['message'] ?? json_encode($payload, JSON_UNESCAPED_UNICODE);
            $context    = array_merge($context, $payload);
        }

        SystemErrorLog::log(
            'error',
            "[{$provider}] 원본 오류: " . mb_substr($rawMessage, 0, 4000),
            $context,
        );
    }

    /**
     * Response를 받아 원본을 시스템 에러로 기록하고, 사용자용 friendly 메시지를 담은 RuntimeException을 반환한다.
     * 사용 예: throw AiError::wrap('OpenAI', __METHOD__, $res);
     */
    public static function wrap(string $provider, string $sourceLabel, Response $response): \RuntimeException
    {
        self::record($provider, $sourceLabel, $response);
        $err = $response->json('error.message') ?? $response->body();
        return new \RuntimeException(self::friendly("{$provider} API 오류: {$err}"));
    }

    /**
     * 사용자에게는 단일 친화 메시지만 노출한다.
     * 원본 진단 정보는 AiError::record() / wrap()이 관리자 시스템 에러 페이지에 별도 기록함.
     * $raw 인자는 호환성을 위해 유지하지만 사용자에게는 노출되지 않는다.
     */
    public static function friendly(string $raw = ''): string
    {
        return '문제가 발생했습니다. 관리자에게 전달되었습니다.';
    }
}
