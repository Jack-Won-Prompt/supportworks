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
// withoutOverlapping() 은 캐시 락을 쓴다. 운영 서버는 CACHE_STORE=file 인데
// 파일 스토어의 락은 샤딩 디렉터리를 만들지 않아 fopen 이 실패하고, 그러면
// schedule:run 전체가 예외로 죽는다(이 서버의 다른 스케줄까지 함께 멈춘다).
// 파일 스토어일 때만 락을 DB 로 돌린다.
//
// 여기서 DB 를 조회하지 않는다(Schema::hasTable 등). 이 파일은 모든 artisan
// 실행마다 로드되므로, DB 에 의존하면 DB 장애가 곧 artisan 장애가 된다.
if (config('cache.default') === 'file') {
    Schedule::useCache('database');
}

Schedule::command('aiw:expire-permissions')->everyMinute()->withoutOverlapping();
Schedule::command('aiw:reap-stale-jobs')->everyMinute()->withoutOverlapping();
// 사람의 답을 오래 기다리는 작업을 다시 알린다. 첫 알림을 놓치면 작업이
// 세션 최대 수명까지 서 있다가 조용히 중단된다.
Schedule::command('aiw:nudge-waiting')->everyMinute()->withoutOverlapping();
Schedule::command('aiw:prune')->dailyAt('03:00');
