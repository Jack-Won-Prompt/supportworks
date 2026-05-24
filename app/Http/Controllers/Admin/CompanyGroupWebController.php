<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\CompanyGroup;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyGroupWebController extends Controller
{
    public function index()
    {
        $groups = CompanyGroup::withCount(['users', 'adminUsers'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.company-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.company-groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'code'             => 'required|string|max:50|unique:company_groups,code|regex:/^[A-Za-z0-9_-]+$/',
            'description'      => 'nullable|string|max:500',
            'is_active'        => 'boolean',
            'uses_withworks'   => 'boolean',
            'shows_in_sr_menu' => 'boolean',
            'path_prefix'      => 'nullable|string|max:200',
        ]);

        CompanyGroup::create([
            'name'             => $request->name,
            'code'             => strtoupper($request->code),
            'description'      => $request->description,
            'is_active'        => $request->boolean('is_active', true),
            'uses_withworks'   => $request->boolean('uses_withworks', false),
            'shows_in_sr_menu' => $request->boolean('shows_in_sr_menu', false),
            'path_prefix'      => $this->normalizePathPrefix($request->input('path_prefix')),
        ]);

        return redirect()->route('admin.company-groups.index')
            ->with('success', '회사 그룹이 생성되었습니다.');
    }

    public function edit(CompanyGroup $companyGroup)
    {
        $companyGroup->loadCount(['users', 'adminUsers']);
        $companyGroup->load('adminUsers');

        $allAdmins = AdminUser::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'login_id', 'role']);

        $assignedAdminIds = $companyGroup->adminUsers->pluck('id')->toArray();

        // 이 그룹 소속 사용자 (페이지 없이 최대 100명)
        $groupUsers = User::where('company_group_id', $companyGroup->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company', 'role']);

        return view('admin.company-groups.edit', compact(
            'companyGroup', 'allAdmins', 'assignedAdminIds', 'groupUsers'
        ));
    }

    public function update(Request $request, CompanyGroup $companyGroup)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:500',
            'is_active'        => 'boolean',
            'uses_withworks'   => 'boolean',
            'shows_in_sr_menu' => 'boolean',
            'path_prefix'      => 'nullable|string|max:200',
        ]);

        $featureKeys = array_keys(\App\Models\CompanyGroup::FEATURE_KEYS);
        $features = [];
        foreach ($featureKeys as $key) {
            $features[$key] = $request->boolean("features_{$key}", true);
        }

        $companyGroup->update([
            'name'             => $request->name,
            'description'      => $request->description,
            'is_active'        => $request->boolean('is_active'),
            'uses_withworks'   => $request->boolean('uses_withworks', false),
            'shows_in_sr_menu' => $request->boolean('shows_in_sr_menu', false),
            'path_prefix'      => $this->normalizePathPrefix($request->input('path_prefix')),
            'features'         => $features,
        ]);

        // 관리자 그룹 할당 동기화
        if ($request->has('admin_ids')) {
            $adminIds = array_filter((array) $request->admin_ids);
            $sync = [];
            foreach ($adminIds as $adminId) {
                $sync[(int) $adminId] = [
                    'can_manage_users' => in_array($adminId, (array) $request->can_manage_users),
                    'can_view_chats'   => true,
                ];
            }
            $companyGroup->adminUsers()->sync($sync);
        } else {
            $companyGroup->adminUsers()->detach();
        }

        return redirect()->route('admin.company-groups.edit', $companyGroup)
            ->with('success', '회사 그룹이 수정되었습니다.');
    }

    /** path_prefix 정규화 — 양쪽 공백/슬래시 제거, 빈 문자열은 null */
    private function normalizePathPrefix(?string $prefix): ?string
    {
        if ($prefix === null) return null;
        $p = trim($prefix);
        $p = ltrim($p, '/\\');
        $p = rtrim($p, '/\\');
        return $p === '' ? null : $p;
    }

    public function destroy(CompanyGroup $companyGroup)
    {
        if ($companyGroup->users()->exists()) {
            return back()->withErrors(['error' => '소속 사용자가 있는 그룹은 삭제할 수 없습니다. 먼저 사용자를 다른 그룹으로 이동하세요.']);
        }

        $companyGroup->delete();
        return redirect()->route('admin.company-groups.index')
            ->with('success', '회사 그룹이 삭제되었습니다.');
    }

    // 사용자 그룹 배정 (AJAX or form)
    public function assignUser(Request $request, CompanyGroup $companyGroup)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        User::where('id', $request->user_id)
            ->update(['company_group_id' => $companyGroup->id]);

        if ($request->wantsJson()) {
            $user = User::find($request->user_id);
            return response()->json(['ok' => true, 'user' => $user->only('id', 'name', 'email', 'company', 'role')]);
        }

        return back()->with('success', '사용자가 그룹에 배정되었습니다.');
    }

    // 사용자 그룹 해제
    public function removeUser(Request $request, CompanyGroup $companyGroup, User $user)
    {
        if ($user->company_group_id !== $companyGroup->id) {
            abort(422, '해당 사용자는 이 그룹 소속이 아닙니다.');
        }

        $user->update(['company_group_id' => null]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '사용자가 그룹에서 제거되었습니다.');
    }

    // 사용자 역할 변경 (AJAX)
    public function updateUserRole(Request $request, CompanyGroup $companyGroup, User $user)
    {
        $request->validate(['role' => 'required|in:admin,member,client']);

        if ((int) $user->company_group_id !== (int) $companyGroup->id) {
            return response()->json(['ok' => false, 'message' => '해당 사용자는 이 그룹 소속이 아닙니다.'], 422);
        }

        $user->update(['role' => $request->role]);

        return response()->json(['ok' => true, 'role' => $user->role]);
    }

    // 미배정 사용자 검색 (AJAX)
    public function searchUnassigned(Request $request)
    {
        $q = $request->input('q', '');

        $users = User::whereNull('company_group_id')
            ->when($q, fn($query) =>
                $query->where(fn($inner) =>
                    $inner->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('company', 'like', "%{$q}%")
                )
            )
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'company']);

        return response()->json($users);
    }
}
