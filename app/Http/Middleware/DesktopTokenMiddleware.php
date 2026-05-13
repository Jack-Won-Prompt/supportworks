<?php

namespace App\Http\Middleware;

use App\Models\DesktopToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DesktopTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if (!$raw) {
            return response()->json(['message' => '인증 토큰이 없습니다.'], 401);
        }

        $token = DesktopToken::findByAccessToken($raw);

        if (!$token) {
            return response()->json(['message' => '유효하지 않거나 만료된 토큰입니다.'], 401);
        }

        // 요청 컨텍스트에 사용자 설정
        $request->setUserResolver(fn() => $token->user);

        return $next($request);
    }
}
