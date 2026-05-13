<?php

namespace App\Http\Controllers\Api\Desktop;

use App\Http\Controllers\Controller;
use App\Models\DesktopToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/desktop/auth/login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->login_id)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login_id' => ['아이디 또는 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        $tokens = DesktopToken::issue($user);

        // 로그인 시 상태를 online으로 변경
        $user->update(['agent_status' => 'online']);

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'status' => $user->agent_status,
            ],
        ]);
    }

    // POST /api/desktop/auth/refresh
    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        $tokenModel = DesktopToken::findByRefreshToken($request->refresh_token);

        if (!$tokenModel) {
            return response()->json(['message' => '리프레시 토큰이 유효하지 않습니다.'], 401);
        }

        $user   = $tokenModel->user;
        $tokens = DesktopToken::issue($user);

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    // POST /api/desktop/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        DesktopToken::where('user_id', $user->id)->delete();
        $user->update(['agent_status' => 'offline']);

        return response()->json(['message' => '로그아웃 되었습니다.']);
    }

    // GET /api/desktop/auth/me
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'status' => $user->agent_status,
            ],
        ]);
    }
}
