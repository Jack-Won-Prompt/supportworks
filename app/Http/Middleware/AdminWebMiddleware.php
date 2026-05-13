<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminWebMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login');
        }
        $admin = auth('admin')->user();
        if ($admin->status !== 'active' || $admin->isLocked()) {
            auth('admin')->logout();
            return redirect()->route('admin.login')->withErrors(['login_id' => '계정이 비활성 상태입니다.']);
        }
        return $next($request);
    }
}
