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
            'local_path'     => $this->localPath,
            'source_branch'  => $this->publish->source_branch,
            'target_branch'  => $this->publish->target_branch,
            'commit_message' => $this->publish->commit_message,
        ];
    }
}
