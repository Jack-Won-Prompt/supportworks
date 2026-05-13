<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    /**
     * 사용법: route()->middleware('admin.role:super_admin,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user();

        if (!$admin || !in_array($admin->role, $roles)) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        return $next($request);
    }
}
