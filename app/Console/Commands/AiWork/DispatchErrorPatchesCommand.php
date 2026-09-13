<?php

namespace App\Console\Commands\AiWork;

use App\Services\AiWork\ErrorPatchDispatcher;
use Illuminate\Console\Command;

/**
 * 판정이 끝난 오류를 작업 지시로 만든다.
 *
 * 웹훅을 받는 자리에서 바로 만들지 않는 이유가 둘 있다.
 *
 *  - 보내는 쪽을 붙잡아 두면 안 된다. 지시를 만드는 일은 담당자 조회·배포 대상
 *    확인·브로드캐스트까지 딸려 있어, 그 사이 운영 사이트의 요청이 서 있게 된다.
 *  - 최소 발생 횟수를 세려면 시간이 지나야 한다. 첫 요청에서 판단하면 모든
 *    오류가 '한 번 난 것' 이다.
 *
 * 그래서 쌓인 것을 주기로 훑는다. 실제로 만들지 여부는 설정
 * (aiw.error_triage.auto_create_jobs)이 정하고, 기본은 꺼져 있다.
 */
class DispatchErrorPatchesCommand extends Command
{
    protected $signature = 'aiw:dispatch-error-patches';

    protected $description = '운영 오류 중 자동 수정 대상을 작업 지시로 만든다';

    public function handle(ErrorPatchDispatcher $dispatcher): int
    {
        if (! config('aiw.error_triage.auto_create_jobs', false)) {
            $this->line('자동 생성이 꺼져 있습니다(aiw.error_triage.auto_create_jobs).');

            return self::SUCCESS;
        }

        $made = $dispatcher->run();

        foreach ($made as $job) {
            $this->line(sprintf('#%d %s', $job->id, $job->title));
        }

        $this->info(count($made).'건을 만들었습니다.');

        return self::SUCCESS;
    }
}
