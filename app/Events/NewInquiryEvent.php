<?php

namespace App\Events;

use App\Models\AdminUser;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewInquiryEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $customerName,
        public string $subject,
        public ?string $firstMessage = null,
    ) {}

    public function broadcastOn(): array
    {
        $groupId = $this->conversation->company_group_id;

        // 웹 app 상담원: 그룹이 있을 때 해당 그룹 상담원에게
        $agentChannels = $groupId
            ? User::where('company_group_id', $groupId)
                ->pluck('id')
                ->map(fn($id) => new PrivateChannel("agent.{$id}"))
                ->all()
            : [];

        // admin_users 알림: 회사 그룹이 있으면 해당 그룹 담당 관리자만, 없으면 전체
        $adminQuery = AdminUser::where('status', 'active');
        if ($groupId) {
            $adminQuery->where(function ($q) use ($groupId) {
                // super_admin은 항상 수신, 그 외는 그룹 배정된 관리자만
                $q->where('role', 'super_admin')
                  ->orWhereHas('companyGroups', fn($cq) => $cq->where('company_groups.id', $groupId));
            });
        }
        $adminChannels = $adminQuery->pluck('id')
            ->map(fn($id) => new PrivateChannel("admin.{$id}"))
            ->all();

        $channels = array_merge($agentChannels, $adminChannels);

        Log::info('NewInquiryEvent::broadcastOn', [
            'conv_id'       => $this->conversation->id,
            'group_id'      => $groupId,
            'channel_names' => array_map(fn($c) => $c->name, $channels),
        ]);

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'       => $this->conversation->id,
            'subject'       => $this->subject,
            'customer_name' => $this->customerName,
            'message'       => $this->firstMessage,
            'created_at'    => $this->conversation->created_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewInquiry';
    }
}
