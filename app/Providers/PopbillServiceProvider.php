<?php

namespace App\Providers;

use App\Services\Popbill\MessageService;
use Illuminate\Support\ServiceProvider;

class PopbillServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }

        $this->app->singleton(MessageService::class);
    }
}
