<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccessToken;
use App\Models\AdminLoginLog;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    private const MAX_FAIL = 5;
    private const LOCK_MINUTES = 30;

    // POST /api/admin/auth/login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $ip    = $request->ip();
        $admin = AdminUser::where('login_id', $request->login_id)->first();

        // 계정 없음
        if (!$admin) {
            AdminLoginLog::create(['login_id' => $request->login_id, 'ip_address' => $ip, 'result' => 'fail']);
            return response()->json(['message' => '아이디 또는 비밀번호가 올바르지 않습니다.'], 401);
        }

        // 잠금 확인
        if ($admin->isLocked()) {
            AdminLoginLog::create(['admin_user_id' => $admin->id, 'login_id' => $request->login_id, 'ip_address' => $ip, 'result' => 'locked']);
            $remaining = now()->diffInMinutes($admin->locked_until, false);
            return response()->json(['message' => "계정이 잠겨 있습니다. {$remaining}분 후 다시 시도하세요."], 403);
        }

        // 비밀번호 불일치
        if (!Hash::check($request->password, $admin->password)) {
            $fails = $admin->login_fail_count + 1;
            $update = ['login_fail_count' => $fails];

            if ($fails >= self::MAX_FAIL) {
                $update['status']       = 'locked';
                $update['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);
            }

            $admin->update($update);
            AdminLoginLog::create(['admin_user_id' => $admin->id, 'login_id' => $request->login_id, 'ip_address' => $ip, 'result' => 'fail']);

            $left = self::MAX_FAIL - $fails;
            $msg  = $left > 0
                ? "비밀번호가 올바르지 않습니다. ({$left}회 남음)"
                : '5회 실패로 계정이 잠겼습니다. 30분 후 다시 시도하세요.';

            return response()->json(['message' => $msg], 401);
        }

        // 비활성 계정
        if ($admin->status !== 'active') {
            return response()->json(['message' => '비활성화된 계정입니다.'], 403);
        }

        // 로그인 성공
        $admin->update(['login_fail_count' => 0, 'last_login_at' => now()]);
        AdminLoginLog::create(['admin_user_id' => $admin->id, 'login_id' => $request->login_id, 'ip_address' => $ip, 'result' => 'success']);

        $tokens = AdminAccessToken::issue($admin, $ip);
        $groups = $admin->isSuperAdmin()
            ? \App\Models\CompanyGroup::where('is_active', true)->get(['id', 'name', 'code'])
            : $admin->companyGroups()->where('is_active', true)->get(['company_groups.id', 'name', 'code']);

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'admin' => [
                'id'              => $admin->id,
                'name'            => $admin->name,
                'login_id'        => $admin->login_id,
                'role'            => $admin->role,
                'must_change_pw'  => $admin->must_change_pw,
                'company_groups'  => $groups,
            ],
        ]);
    }

    // POST /api/admin/auth/refresh
    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        $token = AdminAccessToken::findByRefreshToken($request->refresh_token);

        if (!$token) {
            return response()->json(['message' => '리프레시 토큰이 유효하지 않습니다.'], 401);
        }

        $tokens = AdminAccessToken::issue($token->adminUser, $request->ip());

        return response()->json([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    // POST /api/admin/auth/logout
    public function logout(Request $request): JsonResponse
    {
        AdminAccessToken::where('admin_user_id', $request->user()->id)->delete();

        return response()->json(['message' => '로그아웃 되었습니다.']);
    }

    // GET /api/admin/auth/me
    public function me(Request $request): JsonResponse
    {
        $admin  = $request->user();
        $groups = $admin->isSuperAdmin()
            ? \App\Models\CompanyGroup::where('is_active', true)->get(['id', 'name', 'code'])
            : $admin->companyGroups()->where('is_active', true)->get(['company_groups.id', 'name', 'code']);

        return response()->json([
            'admin' => [
                'id'             => $admin->id,
                'name'           => $admin->name,
                'login_id'       => $admin->login_id,
                'role'           => $admin->role,
                'must_change_pw' => $admin->must_change_pw,
                'company_groups' => $groups,
            ],
        ]);
    }

    // PUT /api/admin/auth/password
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['message' => '현재 비밀번호가 올바르지 않습니다.'], 422);
        }

        $admin->update([
            'password'       => Hash::make($request->new_password),
            'must_change_pw' => false,
        ]);

        return response()->json(['message' => '비밀번호가 변경되었습니다.']);
    }
}
