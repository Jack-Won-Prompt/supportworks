<?php

namespace App\Events;

use App\Models\FileComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileCommentPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FileComment $comment) {}

    public function broadcastOn(): array
    {
        return [new Channel('file.' . $this->comment->project_file_id)];
    }

    public function broadcastWith(): array
    {
        $c = $this->comment;
        return [
            'id'         => $c->id,
            'page'       => $c->page,
            'video_time' => $c->video_time !== null ? (float) $c->video_time : null,
            'content'    => $c->content,
            'user_name'  => $c->user?->name ?? $c->guest_name ?? '외부 리뷰어',
            'user_id'    => $c->user_id,
            'parent_id'  => $c->parent_id,
            'created_at' => $c->created_at->diffForHumans(),
            'can_delete' => false,
            'replies'    => [],
        ];
    }

    public function broadcastAs(): string
    {
        return 'FileCommentPosted';
    }
}
