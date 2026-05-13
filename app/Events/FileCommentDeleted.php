<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileCommentDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $commentId,
        public int $fileId
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('file.' . $this->fileId)];
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->commentId];
    }

    public function broadcastAs(): string
    {
        return 'FileCommentDeleted';
    }
}
