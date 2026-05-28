<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 외부 시스템(withworks 등)에서 보낸 에러를 받아 SystemErrorLog 로 기록.
 *
 * 인증: verify.hmac 미들웨어 — X-WW-Source / X-WW-Signature / X-WW-Timestamp 헤더 필수.
 * 검증 통과 시 request->attributes['hmac.source'] 에 source 키가 들어옴.
 *
 * 페이로드 (JSON):
 *   {
 *     "level":      "error|warning|critical|info" (옵션, 기본 error),
 *     "origin":     "server|client"               (옵션, 기본 server),
 *     "exception":  "Throwable\\Foo"              (옵션),
 *     "message":    "msg",                        (필수)
 *     "file":       "/app/.../X.php",
 *     "line":       42,
 *     "trace":      "stack...",
 *     "context":    { url, method, ip, user_id, user_name, user_agent, route_name, ... },
 *     "occurred_at":"2026-05-28T15:30:00+09:00"   (옵션, 미사용 — created_at 자동)
 *   }
 *
 * 응답: 201 {id} / 422 invalid / 401 hmac fail (미들웨어가 처리)
 */
class ExternalErrorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 메시지 필수
        $message = (string) $request->input('message', '');
        if ($message === '' || mb_strlen($message) > 65000) {
            return response()->json(['error' => 'invalid message'], 422);
        }

        // source 는 미들웨어가 검증한 헤더값을 신뢰. body 의 source 는 무시(스푸핑 방지).
        $source = (string) $request->attributes->get('hmac.source', 'external');

        $level = (string) $request->input('level', 'error');
        $level = in_array($level, ['emergency','alert','critical','error','warning','notice','info','debug'], true)
            ? $level : 'error';

        $origin = (string) $request->input('origin', 'server');
        $origin = in_array($origin, ['server','client','console','job'], true) ? $origin : 'server';

        $context = $request->input('context');
        $context = is_array($context) ? $context : [];

        $record = SystemErrorLog::recordExternal([
            'level'     => $level,
            'source'    => $source,
            'origin'    => $origin,
            'exception' => (string) $request->input('exception', ''),
            'message'   => $message,
            'file'      => (string) $request->input('file', ''),
            'line'      => (int)    $request->input('line', 0),
            'trace'     => (string) $request->input('trace', ''),
            'context'   => $context,
        ]);

        return response()->json(['id' => $record->id], 201);
    }
}
