<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJob;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 작업 PC: 사용자가 수동으로 컨텍스트 정리 요청. */
class HandoverRequested extends AiwEvent
{
    public function __construct(
        public AiwJob $job,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->job->agent_id);
    }

    public function broadcastAs(): string
    {
        return 'handover.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->job->id,
        ];
    }
}
