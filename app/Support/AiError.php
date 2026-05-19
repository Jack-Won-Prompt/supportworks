<?php

namespace App\Support;

class AiError
{
    /**
     * AI 공급자(OpenAI · Claude 등) 원본 오류 메시지를 사용자용 메시지로 변환한다.
     * 사용량·결제 한도, 인증 오류처럼 사용자가 직접 해결할 수 없는 경우
     * "관리자에게 문의하세요" 안내로 바꾸고, 그 외에는 원본을 그대로 반환한다.
     */
    public static function friendly(string $raw): string
    {
        $low = mb_strtolower($raw);

        // 사용량·결제 한도 초과
        $billing = ['quota', 'exceeded your current', 'credit balance', 'billing',
                    'insufficient', 'too low', 'payment required', 'rate limit'];
        foreach ($billing as $kw) {
            if (str_contains($low, $kw)) {
                return 'AI 사용 한도를 초과했거나 결제 확인이 필요합니다. 관리자에게 문의하세요.';
            }
        }

        // 인증 · API 키 오류
        $auth = ['invalid_api_key', 'invalid api key', 'incorrect api key',
                 'unauthorized', 'authentication', 'no api key', '키가 설정되지'];
        foreach ($auth as $kw) {
            if (str_contains($low, $kw)) {
                return 'AI 연동 설정에 문제가 있습니다. 관리자에게 문의하세요.';
            }
        }

        // 일시적 서버 오류 (5xx · 과부하 · 타임아웃)
        $transient = ['server had an error', 'sorry about that', 'try again',
                      'overloaded', 'service unavailable', 'temporarily unavailable',
                      'gateway timeout', 'bad gateway', '502', '503', '504',
                      'internal server error'];
        foreach ($transient as $kw) {
            if (str_contains($low, $kw)) {
                return '일시적인 서버 오류입니다. 잠시 후 다시 시도해 주세요. 계속되면 관리자에게 문의하세요.';
            }
        }

        return $raw;
    }
}
