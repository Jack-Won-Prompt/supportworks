<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJob;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 작업 PC: 취소 요청(사용자 클릭 또는 비용 상한 초과). */
class JobCancelRequested extends AiwEvent
{
    public function __construct(
        public AiwJob $job,
        public string $reason = '',
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->job->agent_id);
    }

    public function broadcastAs(): string
    {
        return 'job.cancel-requested';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->job->id,
            'reason' => $this->reason,
        ];
    }
}
