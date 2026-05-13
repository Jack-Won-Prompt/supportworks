<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // 쿠키(평문) → 세션 → 앱 기본값 순서로 locale 결정
        $locale = $request->cookie('app_locale')  // 암호화 제외된 평문 쿠키
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (!in_array($locale, config('app.available_locales', ['ko', 'en']))) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
