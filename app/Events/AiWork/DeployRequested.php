<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwDeploy;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * 서버 → 담당자 PC: 배포 명령 실행 요청.
 *
 * 운영 서버가 이 서버와 다른 프로젝트를 위해 있다. 담당자 PC 는 이미 각 서버의
 * 접속 키를 들고 있으므로, 이 서버가 남의 운영 서버 키를 갖지 않아도 된다.
 *
 * 명령은 관리자가 등록한 대상 행에서 온다. 요청에서 오지 않는다는 것이
 * 이 기능의 안전장치 전부다 — 담당자 PC 는 받은 문자열을 그대로 셸에 넘긴다.
 */
class DeployRequested extends AiwEvent
{
    public function __construct(
        public AiwDeploy $deploy,
        public int $agentId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->agentId);
    }

    public function broadcastAs(): string
    {
        return 'deploy.requested';
    }

    public function broadcastWith(): array
    {
        $target = $this->deploy->target;

        return [
            'deploy_id'   => $this->deploy->id,
            'job_id'      => $this->deploy->job_id,
            'project_id'  => (int) $target->project_id,
            'name'        => $target->name,
            'working_dir' => $target->working_dir,
            'command'     => $target->command,
            'timeout_sec' => (int) $target->timeout_sec,
        ];
    }
}
