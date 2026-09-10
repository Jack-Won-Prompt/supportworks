<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJob;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 브라우저: 상태·게이지 갱신. 헤더의 컨텍스트/비용 진행바가 이걸로 움직인다. */
class JobStatusChanged extends AiwEvent
{
    public function __construct(public AiwJob $job) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.job.'.$this->job->id);
    }

    public function broadcastAs(): string
    {
        return 'job.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id'               => $this->job->id,
            'status'               => $this->job->status->value,
            'status_label'         => $this->job->status->label(),
            'context_tokens'       => (int) $this->job->context_tokens,
            'context_limit_tokens' => (int) $this->job->context_limit_tokens,
            'cost_usd'             => (float) $this->job->cost_usd,
            'cost_limit_usd'       => (float) $this->job->cost_limit_usd,
            'handover_count'       => (int) $this->job->handover_count,
            'result_summary'       => $this->job->result_summary,
            'error_message'        => $this->job->error_message,
            'finished_at'          => optional($this->job->finished_at)->toIso8601String(),
        ];
    }
}
