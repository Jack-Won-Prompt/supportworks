<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        $channelNames = ['conversation.' . $this->message->conversation_id];

        // 발신자 외 참여자 개인 채널에도 broadcast → 트레이 앱이 자기 채널 하나만 구독해도 알림 수신
        $conv = $this->message->conversation;
        if ($conv) {
            foreach ($conv->participants as $p) {
                if ((int) $p->id !== (int) $this->message->sender_id) {
                    $channelNames[] = 'user.' . $p->id;
                }
            }
        }

        // 모든 active admin에게 알림 전송
        // ※ arrow function은 외부 배열을 값으로 캡처하므로 foreach 사용
        foreach (\App\Models\AdminUser::where('status', 'active')->pluck('id') as $adminId) {
            $channelNames[] = 'admin.' . $adminId;
        }

        $channels = array_map(fn($n) => new PrivateChannel($n), array_unique($channelNames));
        return $channels;
    }

    public function broadcastWith(): array
    {
        $msg = $this->message->load('sender', 'replyTo');
        return [
            'id'                  => $msg->id,
            'room_id'             => $msg->conversation_id,
            'body'                => $msg->body,
            'is_admin'            => str_starts_with($msg->body, '[관리자'),
            'sender_id'           => $msg->sender_id,
            'sender_name'         => $msg->sender->name,
            'file_path'           => $msg->file_path,
            'file_name'           => $msg->file_name,
            'file_size'           => $msg->file_size,
            'is_image'            => $msg->isImage(),
            'file_url'            => $msg->fileUrl(),
            'created_at'          => $msg->created_at->toIso8601String(),
            'date'                => $msg->created_at->format('Y-m-d'),
            'date_label'          => $msg->created_at->format('Y년 m월 d일'),
            'formatted_size'      => $msg->formattedSize(),
            'reply_to_id'         => $msg->reply_to_id,
            'reply_to_body'       => $msg->replyTo?->body,
            'reply_to_sender'     => $msg->replyTo?->sender?->name,
            'reply_to_file_name'  => $msg->replyTo?->file_name,
            'translated_body'     => $msg->translated_body,
            'translate_lang'      => $msg->translate_lang,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
