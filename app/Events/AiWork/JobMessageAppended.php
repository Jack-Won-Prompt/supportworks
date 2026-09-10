<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJobMessage;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 브라우저: 대화 메시지 1건 추가(assistant·handover, 그리고 사용자 본인 메시지 에코). */
class JobMessageAppended extends AiwEvent
{
    public function __construct(public AiwJobMessage $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.job.'.$this->message->job_id);
    }

    public function broadcastAs(): string
    {
        return 'message.appended';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->message->id,
            'job_id'        => $this->message->job_id,
            'seq'           => (int) $this->message->seq,
            'role'          => $this->message->role,
            'content'       => $this->message->content,
            'user_id'       => $this->message->user_id,
            'session_index' => (int) $this->message->session_index,
            'delivered_at'  => optional($this->message->delivered_at)->toIso8601String(),
            'created_at'    => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}
