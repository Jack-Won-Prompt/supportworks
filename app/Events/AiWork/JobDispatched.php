<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJob;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 작업 PC: 지시 실행 요청. 재전송·데몬 복구 시에도 같은 이벤트를 쓴다. */
class JobDispatched extends AiwEvent
{
    public function __construct(
        public AiwJob $job,
        public string $localPath,
        public ?string $defaultBranch = null,
        public ?string $resumeSessionId = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->job->agent_id);
    }

    public function broadcastAs(): string
    {
        return 'job.dispatched';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id'               => $this->job->id,
            'project_id'           => $this->job->project_id,
            'local_path'           => $this->localPath,
            'default_branch'       => $this->defaultBranch,
            'use_branch'           => (bool) $this->job->use_branch,
            'mode'                 => $this->job->mode,
            'model'                => $this->job->model,
            'instruction'          => $this->job->instruction,
            'allowed_tools'        => $this->job->allowed_tools,
            'permission_mode'      => $this->job->permission_mode,
            'context_limit_tokens' => (int) $this->job->context_limit_tokens,
            'cost_limit_usd'       => (float) $this->job->cost_limit_usd,
            'resume_session_id'    => $this->resumeSessionId,
        ];
    }
}
