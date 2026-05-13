<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InquiryAssignedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $agentId,
    ) {}

    public function broadcastOn(): array
    {
        // 웹 상담원 채널
        $channels = [new PrivateChannel("agent.{$this->agentId}")];

        // 동일 그룹의 admin_users 채널에도 알림 (신규 상담 배정 감지용)
        $groupId = $this->conversation->company_group_id;
        if ($groupId) {
            $adminChannels = \App\Models\AdminUser::where('status', 'active')
                ->where(function ($q) use ($groupId) {
                    $q->where('role', 'super_admin')
                      ->orWhereHas('companyGroups', fn($cq) => $cq->where('company_groups.id', $groupId));
                })
                ->pluck('id')
                ->map(fn($id) => new PrivateChannel("admin.{$id}"))
                ->all();
            $channels = array_merge($channels, $adminChannels);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'       => $this->conversation->id,
            'subject'       => $this->conversation->name ?? '',
            'customer_name' => $this->conversation->participants
                ->firstWhere('id', '!=', $this->agentId)?->name ?? '고객',
            'status'        => $this->conversation->status,
        ];
    }

    public function broadcastAs(): string
    {
        return 'InquiryAssigned';
    }
}
