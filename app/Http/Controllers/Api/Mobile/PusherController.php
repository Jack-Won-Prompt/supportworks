<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PusherController extends Controller
{
    /**
     * POST /api/mobile/pusher/auth
     *
     * 모바일 사용자가 Pusher private 채널에 구독할 수 있도록 서명을 발급한다.
     *
     * 허용 채널:
     *   private-conversation.{id}  → 해당 대화 참여자만
     *   private-user.{userId}      → 본인 ID 만
     */
    public function auth(Request $request): JsonResponse
    {
        $request->validate([
            'socket_id'    => 'required|string',
            'channel_name' => 'required|string',
        ]);

        $user        = $request->user();
        $socketId    = $request->socket_id;
        $channelName = $request->channel_name;

        $allowed = false;

        if (preg_match('/^private-conversation\.(\d+)$/', $channelName, $m)) {
            $convId = (int) $m[1];
            $allowed = Conversation::where('id', $convId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->exists();
        } elseif (preg_match('/^private-user\.(\d+)$/', $channelName, $m)) {
            $allowed = ((int) $m[1]) === (int) $user->id;
        }

        if (!$allowed) {
            return response()->json(['message' => '채널 접근 권한이 없습니다.'], 403);
        }

        $pusher = new \Pusher\Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            ['cluster' => config('broadcasting.connections.pusher.options.cluster')]
        );

        $auth = $pusher->authorizeChannel($channelName, $socketId);

        return response()->json(json_decode($auth, true));
    }
}
