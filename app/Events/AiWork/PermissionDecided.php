<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwPermissionRequest;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 작업 PC: 승인 요청 결정(사용자 클릭 또는 타임아웃 만료). */
class PermissionDecided extends AiwEvent
{
    public function __construct(
        public AiwPermissionRequest $request,
        public int $agentId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->agentId);
    }

    public function broadcastAs(): string
    {
        return 'permission.decided';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id'      => $this->request->job_id,
            'request_key' => $this->request->request_key,
            'status'      => $this->request->status,
            'deny_reason' => $this->request->deny_reason,
            'decision'    => $this->request->decisionForDaemon(),
        ];
    }
}
