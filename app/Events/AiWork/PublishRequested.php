<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwPublish;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * 서버 → 담당자 PC: 커밋·머지·푸시 실행 요청.
 *
 * 이 일은 Claude 가 아니라 데몬이 직접 한다. 샌드박스는 Claude 가 무엇을 할 수
 * 있는가를 통제하는 것이고, 사람이 화면에서 누른 버튼은 다른 신뢰 경로다.
 */
class PublishRequested extends AiwEvent
{
    public function __construct(
        public AiwPublish $publish,
        public string $localPath,
        public int $agentId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->agentId);
    }

    public function broadcastAs(): string
    {
        return 'publish.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'publish_id'     => $this->publish->id,
            'job_id'         => $this->publish->job_id,
            // 토큰이 PC 당 하나라 채널도 하나다. 프로젝트별로 나눠 띄운 프로세스가
            // 이 값으로 남의 요청을 걸러낸다 — 없으면 넷이 같은 저장소에서
            // 동시에 커밋을 시도해 index.lock 이 부딪힌다.
            'project_id'     => (int) $this->publish->job?->project_id,
            'local_path'     => $this->localPath,
            'source_branch'  => $this->publish->source_branch,
            'target_branch'  => $this->publish->target_branch,
            'commit_message' => $this->publish->commit_message,
        ];
    }
}
