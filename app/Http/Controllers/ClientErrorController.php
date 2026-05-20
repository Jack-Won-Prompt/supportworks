<?php

namespace App\Http\Controllers;

use App\Models\SystemErrorLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 브라우저(JS) 또는 모바일 앱(Flutter) 에서 발생한 에러를
 * SystemErrorLog 로 받아 저장하고 관리자에게 FCM 알림 발송.
 *
 * 흐름:
 *   브라우저 window.onerror / unhandledrejection
 *     → fetch POST /client-errors  (이 컨트롤러)
 *     → SystemErrorLog::recordClient([...])
 *     → notifyAdmins() — FCM 자동 발송 (5분 쿨다운 dedup)
 *     → 모바일 SystemErrorScreen 에서 확인
 *
 * 인증 없음 (anonymous 브라우저도 보낼 수 있어야). throttle 로 도배 방지.
 */
class ClientErrorController extends Controller
{
    public function store(Request $request): Response
    {
        // 너무 길거나 비정상 페이로드 차단 (방어 코드)
        $message = (string) $request->input('message', '');
        if ($message === '' || mb_strlen($message) > 5000) {
            return response()->noContent(204);
        }

        SystemErrorLog::recordClient(
            data: [
                'exception' => 'JS: ' . (string) $request->input('name', 'Error'),
                'message'   => $message,
                'file'      => (string) $request->input('source', ''),
                'line'      => (int) $request->input('line', 0),
                'trace'     => mb_substr((string) $request->input('stack', ''), 0, 4000),
                'context'   => [
                    'url'         => (string) $request->input('url', ''),
                    'user_agent'  => mb_substr($request->userAgent() ?? '', 0, 500),
                    'ip'          => $request->ip(),
                    'user_id'     => optional($request->user())->id,
                    'origin'      => 'web',
                ],
            ],
            level: 'error',
        );

        return response()->noContent(204);
    }
}