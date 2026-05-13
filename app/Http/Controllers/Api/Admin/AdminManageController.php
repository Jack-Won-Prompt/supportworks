<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManageController extends Controller
{
    // GET /api/admin/admins
    public function index(Request $request): JsonResponse
    {
        $admins = AdminUser::with('companyGroups:id,name,code')
            ->orderBy('role')
            ->orderBy('name')
            ->get([
                'id', 'name', 'login_id', 'email', 'role', 'status',
                'last_login_at', 'must_change_pw', 'created_at',
            ]);

        return response()->json($admins);
    }

    // GET /api/admin/admins/{id}
    public function show(int $id): JsonResponse
    {
        $admin = AdminUser::with('companyGroups:id,name,code')->findOrFail($id);

        return response()->json($admin->makeHidden(['password']));
    }

    // PUT /api/admin/admins/{id}/status
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:active,inactive']);

        $target = AdminUser::findOrFail($id);

        // super_admin 계정 비활성화 불가
        if ($target->role === 'super_admin') {
            return response()->json(['message' => 'super_admin 계정은 비활성화할 수 없습니다.'], 403);
        }

        // 자기 자신 변경 불가
        if ($target->id === $request->user()->id) {
            return response()->json(['message' => '자신의 상태는 변경할 수 없습니다.'], 403);
        }

        $target->update(['status' => $request->status, 'locked_until' => null, 'login_fail_count' => 0]);

        return response()->json(['message' => '상태가 변경되었습니다.']);
    }

    // PUT /api/admin/admins/{id}/role
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $request->validate(['role' => 'required|in:admin,operator,support_agent']);

        // super_admin만 역할 변경 가능
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $target = AdminUser::findOrFail($id);

        if ($target->role === 'super_admin') {
            return response()->json(['message' => 'super_admin 역할은 변경할 수 없습니다.'], 403);
        }

        $target->update(['role' => $request->role]);

        return response()->json(['message' => '역할이 변경되었습니다.']);
    }

    // PUT /api/admin/admins/{id}/groups
    public function updateGroups(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'groups' => 'required|array',
            'groups.*.id' => 'required|integer|exists:company_groups,id',
            'groups.*.can_manage_users' => 'boolean',
            'groups.*.can_view_chats'   => 'boolean',
        ]);

        $target = AdminUser::findOrFail($id);

        $sync = [];
        foreach ($request->groups as $g) {
            $sync[$g['id']] = [
                'can_manage_users' => $g['can_manage_users'] ?? false,
                'can_view_chats'   => $g['can_view_chats'] ?? true,
            ];
        }

        $target->companyGroups()->sync($sync);

        return response()->json(['message' => '그룹 접근 권한이 업데이트되었습니다.']);
    }

    // POST /api/admin/admins/{id}/reset-password
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate(['new_password' => 'required|string|min:8']);

        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $target = AdminUser::findOrFail($id);
        $target->update([
            'password'        => Hash::make($request->new_password),
            'must_change_pw'  => true,
            'login_fail_count'=> 0,
            'status'          => 'active',
            'locked_until'    => null,
        ]);

        return response()->json(['message' => '비밀번호가 초기화되었습니다. 다음 로그인 시 변경해야 합니다.']);
    }
}
