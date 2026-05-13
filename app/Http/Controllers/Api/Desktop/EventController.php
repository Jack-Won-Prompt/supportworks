<?php

namespace App\Http\Controllers\Api\Desktop;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * GET /api/desktop/events/sync?since_event_id=12345
     * since_event_id 이후에 생성된 메시지를 이벤트 형태로 반환
     * (event_id = message.id 를 기준으로 사용)
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate(['since_event_id' => 'required|integer|min:0']);

        $user        = $request->user();
        $sinceId     = (int) $request->since_event_id;

        // 해당 유저가 참여한 대화의 메시지 중 since_id 이후 것
        $messages = Message::where('id', '>', $sinceId)
            ->whereHas('conversation.participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['sender', 'conversation'])
            ->orderBy('id')
            ->limit(200)
            ->get();

        $events = $messages->map(function (Message $m) use ($user) {
            $isOwn = $m->sender_id === $user->id;

            return [
                'type'          => 'message.received',
                'event_id'      => $m->id,
                'room_id'       => $m->conversation_id,
                'message_id'    => $m->id,
                'customer_id'   => $isOwn ? null : $m->sender_id,
                'customer_name' => $isOwn ? null : $m->sender?->name,
                'message'       => $m->body ?? '',
                'created_at'    => $m->created_at->toIso8601String(),
            ];
        })->values();

        return response()->json($events);
    }
}
