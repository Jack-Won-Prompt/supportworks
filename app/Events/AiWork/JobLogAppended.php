<?php

namespace App\Events\AiWork;

use App\Models\AiWork\AiwJobLog;
use Illuminate\Broadcasting\PrivateChannel;

/** 서버 → 브라우저: 활동 로그 1건 추가. */
class JobLogAppended extends AiwEvent
{
    public function __construct(public AiwJobLog $log) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('aiw.job.'.$this->log->job_id);
    }

    public function broadcastAs(): string
    {
        return 'log.appended';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->log->id,
            'job_id'     => $this->log->job_id,
            'seq'        => (int) $this->log->seq,
            'type'       => $this->log->type,
            'content'    => $this->log->content,
            // raw 는 브라우저로 보내지 않는다. 목록이 무거워지고 화면에서도 쓰지 않는다.
            'created_at' => optional($this->log->created_at)->toIso8601String(),
        ];
    }
}
