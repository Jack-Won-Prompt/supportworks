<?php

namespace App\Http\Middleware;

use App\Models\AiWork\AiwAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AI Works 데몬 토큰 인증.
 *
 * DesktopTokenMiddleware 패턴을 따르되, 데몬은 사용자가 아니라 기계라서
 * setUserResolver 를 하지 않는다. 대신 request attribute 로 에이전트를 넘긴다.
 *
 * 이 토큰은 사실상 해당 PC 에서 명령을 실행할 권한이므로, 만료와 IP 제한으로
 * 유출 시 창을 좁힌다.
 */
class AiwAgentMiddleware
{
    public const ATTRIBUTE = 'aiw_agent';

    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if (! $raw) {
            return response()->json(['message' => '인증 토큰이 없습니다.'], 401);
        }

        // findByToken 은 만료된 토큰을 애초에 찾지 않는다.
        $agent = AiwAgent::findByToken($raw);

        if (! $agent) {
            return response()->json(['message' => '유효하지 않거나 만료된 토큰입니다.'], 401);
        }

        if (! $agent->allowsIp($request->ip())) {
            return response()->json(['message' => '허용되지 않은 IP 입니다.'], 403);
        }

        // 마지막 사용 IP 는 감사용. 하트비트마다 쓰지 않도록 값이 바뀔 때만 저장한다.
        if ($agent->last_used_ip !== $request->ip()) {
            $agent->forceFill(['last_used_ip' => $request->ip()])->saveQuietly();
        }

        $request->attributes->set(self::ATTRIBUTE, $agent);

        return $next($request);
    }
}
