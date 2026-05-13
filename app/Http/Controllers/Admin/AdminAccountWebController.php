<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\CompanyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAccountWebController extends Controller
{
    public function index()
    {
        $admins = AdminUser::with('companyGroups:id,name,code')
            ->orderByRaw("FIELD(role,'super_admin','admin','operator','support_agent')")
            ->orderBy('name')
            ->paginate(20);

        $groups = CompanyGroup::where('is_active', true)->orderBy('name')->get();

        return view('admin.admins.index', compact('admins', 'groups'));
    }

    public function create()
    {
        $groups = CompanyGroup::where('is_active', true)->orderBy('name')->get();
        return view('admin.admins.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'login_id'  => 'required|string|max:50|unique:admin_users,login_id|regex:/^[A-Za-z0-9_]+$/',
            'email'     => 'required|email|unique:admin_users,email',
            'phone'     => 'nullable|string|max:30',
            'password'  => 'required|string|min:8|confirmed',
            'role'      => 'required|in:admin,operator,support_agent',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:company_groups,id',
        ], [
            'login_id.regex'  => '로그인 ID는 영문, 숫자, 언더스코어(_)만 사용할 수 있습니다. (@, 공백, 특수문자 불가)',
            'login_id.unique' => '이미 사용 중인 로그인 ID입니다.',
        ]);

        $admin = AdminUser::create([
            'name'     => $request->name,
            'login_id' => $request->login_id,
            'email'    => $request->email,
            'phone'    => $request->filled('phone') ? preg_replace('/[^\d+\-\s]/', '', trim($request->phone)) : null,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'active',
        ]);

        if ($request->filled('group_ids')) {
            $sync = [];
            foreach ($request->group_ids as $gId) {
                $sync[(int)$gId] = [
                    'can_manage_users' => in_array($gId, (array) $request->can_manage_users),
                    'can_view_chats'   => true,
                ];
            }
            $admin->companyGroups()->sync($sync);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.admins.index')
            ->with('success', '관리자 계정이 생성되었습니다.');
    }

    public function edit(AdminUser $admin)
    {
        $admin->load('companyGroups');
        $groups        = CompanyGroup::where('is_active', true)->orderBy('name')->get();
        $assignedIds   = $admin->companyGroups->pluck('id')->toArray();
        $manageUserIds = $admin->companyGroups
            ->filter(fn($g) => $g->pivot->can_manage_users)
            ->pluck('id')->toArray();

        return view('admin.admins.edit', compact('admin', 'groups', 'assignedIds', 'manageUserIds'));
    }

    public function update(Request $request, AdminUser $admin)
    {
        $me = auth('admin')->user();

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:admin_users,email,'.$admin->id,
            'phone'    => 'nullable|string|max:30',
            'role'     => 'required|in:admin,operator,support_agent',
            'status'   => 'required|in:active,inactive',
            'password' => 'nullable|string|min:8|confirmed',
            'group_ids'  => 'nullable|array',
            'group_ids.*'=> 'exists:company_groups,id',
        ]);

        // super_admin 보호
        if ($admin->role === 'super_admin') {
            return back()->withErrors(['error' => 'super_admin 계정은 수정할 수 없습니다.']);
        }

        $data = [
            'name'   => $request->name,
            'email'  => $request->email,
            'phone'  => $request->filled('phone') ? preg_replace('/[^\d+\-\s]/', '', trim($request->phone)) : null,
            'role'   => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password']         = Hash::make($request->password);
            $data['login_fail_count'] = 0;
            $data['locked_until']     = null;
        }

        $admin->update($data);

        // 그룹 동기화
        $sync = [];
        foreach ((array) $request->group_ids as $gId) {
            if (!$gId) continue;
            $sync[(int)$gId] = [
                'can_manage_users' => in_array($gId, (array) $request->can_manage_users),
                'can_view_chats'   => true,
            ];
        }
        $admin->companyGroups()->sync($sync);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.admins.index')
            ->with('success', '관리자 계정이 수정되었습니다.');
    }

    public function destroy(AdminUser $admin)
    {
        $me = auth('admin')->user();

        if ($admin->role === 'super_admin') {
            return back()->withErrors(['error' => 'super_admin 계정은 삭제할 수 없습니다.']);
        }
        if ($admin->id === $me->id) {
            return back()->withErrors(['error' => '자신의 계정은 삭제할 수 없습니다.']);
        }

        $admin->update(['status' => 'inactive']);

        return back()->with('success', '관리자 계정이 비활성화되었습니다.');
    }
}
