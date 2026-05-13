<?php

namespace App\Http\Middleware;

use App\Models\AdminAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if (!$raw) {
            return response()->json(['message' => '인증 토큰이 없습니다.'], 401);
        }

        $token = AdminAccessToken::findByAccessToken($raw);

        if (!$token) {
            return response()->json(['message' => '유효하지 않거나 만료된 토큰입니다.'], 401);
        }

        $admin = $token->adminUser;

        if ($admin->status !== 'active' || $admin->isLocked()) {
            return response()->json(['message' => '비활성화되거나 잠긴 계정입니다.'], 403);
        }

        $request->setUserResolver(fn() => $admin);

        return $next($request);
    }
}
