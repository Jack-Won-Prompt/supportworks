<?php

namespace App\Services\AiWork;

use App\Jobs\AiWork\RunDeploy;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\User;
use RuntimeException;

/**
 * 배포 실행 요청.
 *
 * 이 서비스가 지키는 것은 세 가지다.
 *   1. 명령은 등록된 대상에서만 온다 — 요청은 "어느 대상"만 고른다.
 *   2. 같은 대상을 동시에 두 번 돌리지 않는다 — 배포 중 배포는 저장소를 망가뜨린다.
 *   3. 실수 클릭을 막는다 — 대상 이름을 그대로 입력해야 진행한다.
 */
class DeployService
{
    /**
     * @throws RuntimeException 지금 배포할 수 없을 때
     */
    public function request(
        AiwDeployTarget $target,
        User $user,
        string $confirmation,
        ?AiwJob $job = null,
    ): AiwDeploy {
        if (! $target->enabled) {
            throw new RuntimeException('비활성 상태인 배포 대상입니다.');
        }

        // 되돌리기 비용이 큰 동작이다. 누르는 순간 무엇이 도는지 알고 있어야 한다.
        if (trim($confirmation) !== $target->confirmPhrase()) {
            throw new RuntimeException(
                sprintf('확인을 위해 대상 이름 "%s" 을(를) 정확히 입력하세요.', $target->confirmPhrase()),
            );
        }

        if ($target->isBusy()) {
            throw new RuntimeException('이미 배포가 진행 중입니다. 끝나면 다시 시도하세요.');
        }

        if ($job && (int) $job->project_id !== (int) $target->project_id) {
            throw new RuntimeException('이 작업의 프로젝트에 속한 배포 대상이 아닙니다.');
        }

        $deploy = AiwDeploy::create([
            'target_id'    => $target->id,
            'job_id'       => $job?->id,
            'requested_by' => $user->id,
            'status'       => 'queued',
            'created_at'   => now(),
        ]);

        // 웹 요청 안에서 돌리지 않는다. deploy.sh 는 수 분이 걸린다.
        RunDeploy::dispatch($deploy->id);

        return $deploy;
    }
}
