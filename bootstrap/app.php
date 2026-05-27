<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // auth 미들웨어 제거: admin guard 사용자도 channels.php에서 처리
            // 진단 로그(임시): Broadcast::auth 호출 직전 인증/세션 상태 기록 → 콜백 진입 전 거절 케이스 추적
            \Illuminate\Support\Facades\Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
                $rememberKey = 'remember_web_' . sha1(\App\Models\User::class);
                \Log::info('[bcast-auth] enter ch=' . (string) $request->input('channel_name')
                    . ' web=' . (auth('web')->check() ? auth('web')->id() : 'null')
                    . ' admin=' . (auth('admin')->check() ? auth('admin')->id() : 'null')
                    . ' sid=' . substr((string) session()->getId(), 0, 8)
                    . ' has_remember=' . ($request->hasCookie($rememberKey) ? 'y' : 'n'));
                return \Illuminate\Support\Facades\Broadcast::auth($request);
            })->middleware('web')->name('broadcasting.auth');
            require base_path('routes/channels.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'        => \App\Http\Middleware\AdminMiddleware::class,
            'admin.web'    => \App\Http\Middleware\AdminWebMiddleware::class,
            'desktop.auth' => \App\Http\Middleware\DesktopTokenMiddleware::class,
            'mobile.auth'  => \App\Http\Middleware\MobileTokenMiddleware::class,
            'admin.token'  => \App\Http\Middleware\AdminTokenMiddleware::class,
            'admin.role'   => \App\Http\Middleware\AdminRoleMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: ['collab/heartbeat', 'client-errors']);
        $middleware->encryptCookies(except: ['app_locale']);
        // 이미 로그인된 사용자가 /login, /register 등 guest 라우트 진입 시 바로 대시보드로
        $middleware->redirectUsersTo('/dashboard');
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\LogPageAccess::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CollabParticipantMiddleware::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\MaintenanceCheckMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e): void {
            // 404/403/419(CSRF) 등 일반적인 HTTP 예외는 기록하지 않음
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                && in_array($e->getStatusCode(), [401, 403, 404, 419, 422])) {
                return;
            }
            \App\Models\SystemErrorLog::record($e);
        });
    })->create();
