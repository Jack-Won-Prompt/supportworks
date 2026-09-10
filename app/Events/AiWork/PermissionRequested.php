<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwPermissionRequest;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 브라우저: 승인 요청 카드를 대화 흐름에 삽입한다. */
class PermissionRequested extends AiwEvent
{
    public function __construct(public AiwPermissionRequest $request) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.job.'.$this->request->job_id);
    }

    public function broadcastAs(): string
    {
        return 'permission.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->request->id,
            'job_id'      => $this->request->job_id,
            'request_key' => $this->request->request_key,
            'tool_name'   => $this->request->tool_name,
            'tool_input'  => $this->request->tool_input,
            'status'      => $this->request->status,
            'created_at'  => optional($this->request->created_at)->toIso8601String(),
        ];
    }
}
