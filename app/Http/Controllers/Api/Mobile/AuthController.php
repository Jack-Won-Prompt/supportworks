<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['이메일 또는 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        $tokens = MobileToken::issue($user);

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user'          => $this->userResource($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'company'  => $request->company,
            'phone'    => $request->phone,
            'role'     => 'member',
        ]);

        $tokens = MobileToken::issue($user);

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user'          => $this->userResource($user),
        ], 201);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        $tokenModel = MobileToken::findByRefreshToken($request->refresh_token);

        if (!$tokenModel) {
            return response()->json(['message' => '리프레시 토큰이 유효하지 않습니다.'], 401);
        }

        $tokens = MobileToken::issue($tokenModel->user);

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        MobileToken::where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => '로그아웃 되었습니다.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userResource($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'    => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:20',
        ]);

        $user->update($request->only('name', 'company', 'phone'));

        return response()->json(['user' => $this->userResource($user)]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => '현재 비밀번호가 올바르지 않습니다.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => '비밀번호가 변경되었습니다.']);
    }

    private function userResource(User $user): array
    {
        return [
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'company'          => $user->company,
            'phone'            => $user->phone,
            'role'             => $user->role,
            'avatar'           => $user->avatar,
            'company_group_id' => $user->company_group_id,
        ];
    }
}