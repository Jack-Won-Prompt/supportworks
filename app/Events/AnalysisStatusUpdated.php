<?php

namespace App\Events;

use App\Models\AnalysisSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalysisStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly AnalysisSession $session) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('analysis-session.' . $this->session->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id'    => $this->session->id,
            'status'        => $this->session->status,
            'status_label'  => $this->session->status_label,
            'candidate_count' => count($this->session->candidates),
            'error_message' => $this->session->error_message,
        ];
    }
}
