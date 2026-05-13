<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyGroupController extends Controller
{
    // GET /api/admin/company-groups
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();

        $groups = $admin->isSuperAdmin()
            ? CompanyGroup::withCount('adminAccesses')->orderBy('name')->get()
            : $admin->companyGroups()->withCount('adminAccesses')->orderBy('name')
                    ->get(['company_groups.id', 'name', 'code', 'description', 'is_active']);

        return response()->json($groups);
    }

    // POST /api/admin/company-groups
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:50|unique:company_groups,code',
            'description' => 'nullable|string',
        ]);

        $group = CompanyGroup::create([
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return response()->json($group, 201);
    }

    // PUT /api/admin/company-groups/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
        ]);

        $group = CompanyGroup::findOrFail($id);
        $group->update($request->only('name', 'description', 'is_active'));

        return response()->json($group);
    }

    // GET /api/admin/company-groups/{id}/admins
    // 해당 그룹에 배정된 관리자(admin_users) 목록
    public function admins(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();

        if (!$admin->canAccessGroup($id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $group = CompanyGroup::with(['adminAccesses.adminUser:id,name,login_id,email,role,status'])
            ->findOrFail($id);

        return response()->json([
            'group'  => $group->only('id', 'name', 'code'),
            'admins' => $group->adminAccesses->map(fn($a) => [
                'admin_user'       => $a->adminUser,
                'can_manage_users' => $a->can_manage_users,
                'can_view_chats'   => $a->can_view_chats,
            ]),
        ]);
    }

    // GET /api/admin/company-groups/{id}/web-users
    // 해당 그룹에 속한 웹 사용자 목록
    public function webUsers(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();

        if (!$admin->canAccessGroup($id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $group = CompanyGroup::findOrFail($id);

        $users = User::where('company_group_id', $id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company', 'phone', 'agent_status', 'created_at']);

        return response()->json([
            'group' => $group->only('id', 'name', 'code'),
            'users' => $users,
            'total' => $users->count(),
        ]);
    }

    // PUT /api/admin/company-groups/{id}/web-users/{userId}
    // 특정 웹 유저를 그룹에 배정
    public function assignWebUser(Request $request, int $id, int $userId): JsonResponse
    {
        $admin = $request->user();

        if (!$admin->canAccessGroup($id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        CompanyGroup::findOrFail($id);
        $user = User::findOrFail($userId);
        $user->update(['company_group_id' => $id]);

        return response()->json(['message' => "{$user->name}님이 그룹에 배정되었습니다."]);
    }

    // DELETE /api/admin/company-groups/{id}/web-users/{userId}
    // 웹 유저를 그룹에서 제거
    public function removeWebUser(Request $request, int $id, int $userId): JsonResponse
    {
        $admin = $request->user();

        if (!$admin->canAccessGroup($id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $user = User::where('id', $userId)->where('company_group_id', $id)->firstOrFail();
        $user->update(['company_group_id' => null]);

        return response()->json(['message' => "{$user->name}님이 그룹에서 제거되었습니다."]);
    }

    // GET /api/admin/company-groups/{id}/web-users/unassigned
    // 아직 그룹 미배정 사용자 검색 (배정 화면용)
    public function unassignedWebUsers(Request $request, int $id): JsonResponse
    {
        $request->validate(['search' => 'nullable|string|max:100']);

        CompanyGroup::findOrFail($id);

        $users = User::whereNull('company_group_id')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($inner) use ($s) {
                    $inner->where('name', 'like', "%{$s}%")
                          ->orWhere('email', 'like', "%{$s}%")
                          ->orWhere('company', 'like', "%{$s}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email', 'company']);

        return response()->json($users);
    }
}
