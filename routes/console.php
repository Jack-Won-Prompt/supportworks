<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('prompt-sessions:cleanup')->everyTenMinutes();

/*
|--------------------------------------------------------------------------
| AI Works
|--------------------------------------------------------------------------
| 이 세 개가 돌지 않으면 승인 대기가 영원히 풀리지 않고, 데몬이 죽은 job 이
| "실행 중"으로 남으며, 로그 원문이 무한정 쌓인다. 운영에서는
| supportworks-schedule.service(systemd, schedule:work)가 이를 실행한다.
*/
Schedule::command('aiw:expire-permissions')->everyMinute()->withoutOverlapping();
Schedule::command('aiw:reap-stale-jobs')->everyMinute()->withoutOverlapping();
Schedule::command('aiw:prune')->dailyAt('03:00');
