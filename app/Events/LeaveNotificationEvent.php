<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int    $targetUserId  수신 대상 user id
     * @param string $type          leave_requested | leave_approved | leave_rejected
     * @param string $actorName     행위자 이름
     * @param string $leaveLabel    휴무 유형 레이블
     * @param string $dateRange     기간 문자열
     * @param string $url           휴무 페이지 URL
     */
    public function __construct(
        public int    $targetUserId,
        public string $type,
        public string $actorName,
        public string $leaveLabel,
        public string $dateRange,
        public string $url,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->targetUserId)];
    }

    public function broadcastWith(): array
    {
        return [
            'type'        => $this->type,
            'actor_name'  => $this->actorName,
            'leave_label' => $this->leaveLabel,
            'date_range'  => $this->dateRange,
            'url'         => $this->url,
        ];
    }

    public function broadcastAs(): string
    {
        return 'LeaveNotification';
    }
}
