<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 사용자 영역의 SR 담당자 메뉴 가드.
 * 통과 조건: 로그인 + (User::isAdmin() OR User::isSr())
 *
 * 시스템 에러 관리는 운영상 민감하므로 일반 member 접근을 차단.
 */
class RequireSrOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isAdmin() && !$user->isSr()) {
            abort(403, 'SR 담당자 또는 관리자만 접근할 수 있습니다.');
        }

        return $next($request);
    }
}
