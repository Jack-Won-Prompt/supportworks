<?php

namespace App\Providers;

use App\View\Composers\AiAgentComposer;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // app_locale 쿠키는 평문으로 저장/읽기 (locale 전환용)
        EncryptCookies::except(['app_locale']);
    }

    public function boot(): void
    {
        $appUrl = config('app.url');
        if ($appUrl) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        View::composer('ai-agent.*', AiAgentComposer::class);
    }
}
