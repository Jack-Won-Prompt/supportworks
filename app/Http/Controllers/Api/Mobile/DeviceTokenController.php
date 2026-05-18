<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /** POST /device-tokens - FCM 토큰 등록/갱신 */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string|max:512',
            'platform' => 'nullable|in:android,ios',
        ]);

        $user = $request->user();

        // 동일 토큰이 있으면 현재 사용자로 갱신 (기기 소유자 변경 대응)
        DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'      => $user->id,
                'platform'     => $request->platform ?? 'android',
                'last_used_at' => now(),
            ],
        );

        return response()->json(['message' => '토큰이 등록되었습니다.']);
    }

    /** POST /device-tokens/remove - 토큰 제거 (로그아웃) */
    public function remove(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        DeviceToken::where('token', $request->token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => '토큰이 제거되었습니다.']);
    }
}