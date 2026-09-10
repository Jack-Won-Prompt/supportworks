<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJob;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 작업 PC: 대화형 세션 종료 요청. */
class JobEndRequested extends AiwEvent
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
        return 'job.end-requested';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->job->id,
        ];
    }
}
