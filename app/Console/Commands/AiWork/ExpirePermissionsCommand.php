<?php

namespace App\Console\Commands\AiWork;

use App\Services\AiWork\PermissionService;
use Illuminate\Console\Command;

/**
 * 방치된 승인 요청을 만료시킨다.
 *
 * 이게 돌지 않으면 사용자가 자리를 비운 사이 데몬이 무한정 대기하고, job 이
 * waiting_permission 에 영원히 갇힌다.
 */
class ExpirePermissionsCommand extends Command
{
    protected $signature = 'aiw:expire-permissions';

    protected $description = 'AI Works: 타임아웃된 승인 요청을 만료 처리한다';

    public function handle(PermissionService $permissions): int
    {
        $count = $permissions->expireStale();

        if ($count > 0) {
            $this->info("승인 요청 {$count}건을 만료 처리했습니다.");
        }

        return self::SUCCESS;
    }
}
