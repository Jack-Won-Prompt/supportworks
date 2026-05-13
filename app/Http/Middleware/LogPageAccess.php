<?php

namespace App\Http\Middleware;

use App\Models\UserPageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('GET')) {
            return $response;
        }

        if (!auth()->check()) {
            return $response;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $response;
        }

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $routeName = $request->route()?->getName();

        if (!$routeName || !isset(UserPageLog::SCREEN_MAP[$routeName])) {
            return $response;
        }

        UserPageLog::create([
            'user_id'     => auth()->id(),
            'route_name'  => $routeName,
            'screen_name' => UserPageLog::SCREEN_MAP[$routeName],
            'url'         => $request->url(),
            'ip_address'  => $request->ip(),
        ]);

        return $response;
    }
}
