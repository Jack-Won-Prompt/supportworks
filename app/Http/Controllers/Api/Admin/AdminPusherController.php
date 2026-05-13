<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class AdminPusherController extends Controller
{
    /**
     * POST /api/admin/pusher/auth
     * admin_users가 Pusher private 채널에 구독할 수 있도록 서명
     */
    public function auth(Request $request): JsonResponse
    {
        $request->validate([
            'socket_id'    => 'required|string',
            'channel_name' => 'required|string',
        ]);

        $admin       = $request->user();
        $socketId    = $request->socket_id;
        $channelName = $request->channel_name;

        // 허용 채널:
        //   private-conversation.{id}  → 그룹 접근 권한 확인
        //   private-admin.{id}         → 본인 id만 허용 (admin_users 전용)
        $allowed = false;

        if (preg_match('/^private-conversation\.(\d+)$/', $channelName, $m)) {
            $convId = (int) $m[1];
            $conv   = \App\Models\Conversation::find($convId);
            if ($conv && ($admin->isSuperAdmin() || ($conv->company_group_id && $admin->canAccessGroup($conv->company_group_id)))) {
                $allowed = true;
            }
        } elseif (preg_match('/^private-admin\.(\d+)$/', $channelName, $m)) {
            $allowed = (int) $m[1] === $admin->id;
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
