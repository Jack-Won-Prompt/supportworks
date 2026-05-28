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
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'        => \App\Http\Middleware\AdminMiddleware::class,
            'admin.web'    => \App\Http\Middleware\AdminWebMiddleware::class,
            'desktop.auth' => \App\Http\Middleware\DesktopTokenMiddleware::class,
            'mobile.auth'  => \App\Http\Middleware\MobileTokenMiddleware::class,
            'admin.token'  => \App\Http\Middleware\AdminTokenMiddleware::class,
            'admin.role'   => \App\Http\Middleware\AdminRoleMiddleware::class,
            'verify.hmac'  => \App\Http\Middleware\VerifyHmacSignature::class,
            'sr.or.admin'  => \App\Http\Middleware\RequireSrOrAdmin::class,
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
