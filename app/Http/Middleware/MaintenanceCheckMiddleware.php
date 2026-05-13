<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;

class MaintenanceCheckMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 관리자 영역(/admin/*) 및 관리자 로그인은 항상 통과
        if ($request->is('admin', 'admin/*')) {
            return $next($request);
        }

        // 이미 관리자로 로그인한 세션은 사용자 영역도 미리보기 가능하게 통과
        if (auth('admin')->check()) {
            return $next($request);
        }

        if (!SystemSetting::isMaintenanceMode()) {
            return $next($request);
        }

        // AJAX / JSON 요청은 503 JSON 응답
        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => false,
                'error'   => 'maintenance',
                'message' => SystemSetting::current()->maintenance_message,
            ], 503);
        }

        return response()->view('maintenance', [
            'message' => SystemSetting::current()->maintenance_message,
        ], 503);
    }
}
