<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * 외부 시스템(withworks 등)에서 보낸 요청의 HMAC-SHA256 서명을 검증.
 *
 * 요청 헤더:
 *   X-WW-Source     : 'withworks' 등 (config('services.external_errors.sources')에 등록되어야 함)
 *   X-WW-Signature  : 'sha256=<hex>' — body 의 hash_hmac
 *   X-WW-Timestamp  : Unix ts (5분 윈도우 — replay 방지)
 *
 * 라우트 등록 예:
 *   Route::middleware('verify.hmac')->post('/api/external-errors', ...)
 */
class VerifyHmacSignature
{
    private const TIMESTAMP_TOLERANCE_SECONDS = 300; // 5 min

    public function handle(Request $request, Closure $next): HttpResponse
    {
        $source = (string) $request->header('X-WW-Source', '');
        $signature = (string) $request->header('X-WW-Signature', '');
        $timestamp = (string) $request->header('X-WW-Timestamp', '');

        if ($source === '' || $signature === '' || $timestamp === '') {
            return $this->reject('Missing HMAC headers');
        }

        // 등록된 source 인지 확인 + secret 가져오기
        $secrets = config('services.external_errors.sources', []);
        $secret = $secrets[$source] ?? null;
        if (!$secret) {
            return $this->reject('Unknown source');
        }

        // 타임스탬프 윈도우 검증 (replay 방지)
        $ts = (int) $timestamp;
        if ($ts <= 0 || abs(time() - $ts) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return $this->reject('Timestamp out of window');
        }

        // 서명 검증: sha256=<hex>
        if (!preg_match('/^sha256=([a-f0-9]{64})$/i', $signature, $m)) {
            return $this->reject('Malformed signature');
        }
        $providedHex = strtolower($m[1]);

        // 서명 대상: timestamp + "\n" + body (raw)
        $body = (string) $request->getContent();
        $expected = hash_hmac('sha256', $timestamp . "\n" . $body, $secret);

        if (!hash_equals($expected, $providedHex)) {
            return $this->reject('Signature mismatch');
        }

        // 검증 통과 — downstream 에서 사용할 수 있도록 source 주입
        $request->attributes->set('hmac.source', $source);

        return $next($request);
    }

    private function reject(string $reason): HttpResponse
    {
        // 본문 누설 최소화 — 운영 로그에는 reason 기록
        \Log::warning('VerifyHmacSignature rejected: ' . $reason);
        return new Response('Unauthorized', 401);
    }
}
