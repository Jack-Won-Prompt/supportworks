<?php

namespace App\Events;

use App\Models\MessageImageComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageCommentPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MessageImageComment $comment) {}

    public function broadcastOn(): array
    {
        $convId = $this->comment->message->conversation_id;
        return [new PrivateChannel('conversation.' . $convId)];
    }

    public function broadcastAs(): string { return 'ImageCommentPosted'; }

    public function broadcastWith(): array
    {
        $c = $this->comment;
        return [
            'message_id' => $c->message_id,
            'comment'    => [
                'id'         => $c->id,
                'content'    => $c->content,
                'user_name'  => $c->displayName(),
                'user_id'    => $c->user_id,
                'created_at' => $c->created_at->format('m/d H:i'),
            ],
        ];
    }
}
