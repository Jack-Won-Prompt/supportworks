<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        $channelNames = ['conversation.' . $this->message->conversation_id];

        $conv = $this->message->conversation;
        if ($conv) {
            foreach ($conv->participants as $p) {
                if ((int) $p->id !== (int) $this->message->sender_id) {
                    $channelNames[] = 'user.' . $p->id;
                }
            }
        }

        $channels = array_map(fn($n) => new PrivateChannel($n), array_unique($channelNames));
        return $channels;
    }

    public function broadcastWith(): array
    {
        $msg = $this->message;
        return [
            'id'          => $msg->id,
            'room_id'     => $msg->conversation_id,
            'reply_to_id' => $msg->reply_to_id,
            'sender_id'   => $msg->sender_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageDeleted';
    }
}
