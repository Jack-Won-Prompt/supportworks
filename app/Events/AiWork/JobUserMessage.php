<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJobMessage;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 작업 PC: 대화형 모드에서 사용자가 보낸 메시지. */
class JobUserMessage extends AiwEvent
{
    public function __construct(
        public AiwJobMessage $message,
        public int $agentId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.agent.'.$this->agentId);
    }

    public function broadcastAs(): string
    {
        return 'job.user-message';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id'     => $this->message->job_id,
            'message_id' => $this->message->id,
            'seq'        => (int) $this->message->seq,
            'content'    => $this->message->content,
            'attachments' => $this->message->attachments()
                ->get(['id', 'mime', 'original_name'])
                ->map(fn ($a) => ['id' => $a->id, 'mime' => $a->mime, 'name' => $a->original_name])
                ->values(),
        ];
    }
}
