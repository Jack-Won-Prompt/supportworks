<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewAdminMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message      $message,
        public Conversation $conversation,
        public int          $targetUserId,
        public string       $adminName = '관리자'
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->targetUserId)];
    }

    public function broadcastWith(): array
    {
        $msg  = $this->message;
        $conv = $this->conversation;

        // body에서 "[관리자 name] " prefix 제거하여 순수 메시지만 전달
        $body = preg_replace('/^\[관리자\s[^\]]*\]\s*/', '', $msg->body);

        return [
            'conv_id'        => $conv->id,
            'message_id'     => $msg->id,
            'body'           => $body,
            'admin_name'     => $this->adminName,
            'created_at'     => $msg->created_at->toIso8601String(),
            'created_at_fmt' => $msg->created_at->format('H:i'),
            'date'           => $msg->created_at->format('Y-m-d'),
            'date_label'     => $msg->created_at->format('Y년 m월 d일'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewAdminMessage';
    }
}
