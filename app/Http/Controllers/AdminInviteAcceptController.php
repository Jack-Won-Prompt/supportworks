<?php

namespace App\Http\Controllers;

use App\Models\AdminInvitation;
use App\Models\AdminUser;
use App\Models\AdminCompanyGroupAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminInviteAcceptController extends Controller
{
    // GET /admin/invite/accept/{token}
    public function show(string $token)
    {
        $invitation = AdminInvitation::findValidToken($token);

        if (!$invitation) {
            return view('admin.invite-invalid', ['reason' => '초대 링크가 만료되었거나 유효하지 않습니다.']);
        }

        return view('admin.invite-accept', compact('invitation', 'token'));
    }

    // POST /admin/invite/accept/{token}
    public function accept(Request $request, string $token)
    {
        $invitation = AdminInvitation::findValidToken($token);

        if (!$invitation) {
            return back()->withErrors(['token' => '초대 링크가 만료되었거나 유효하지 않습니다.']);
        }

        $request->validate([
            'login_id' => 'required|string|min:4|max:50|regex:/^[a-zA-Z0-9_]+$/|unique:admin_users,login_id',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'login_id.regex'  => '아이디는 영문, 숫자, 언더스코어만 사용 가능합니다.',
            'login_id.unique' => '이미 사용 중인 아이디입니다.',
        ]);

        DB::transaction(function () use ($request, $invitation) {
            $admin = AdminUser::create([
                'name'        => $invitation->name,
                'login_id'    => $request->login_id,
                'email'       => $invitation->email,
                'password'    => Hash::make($request->password),
                'role'        => $invitation->role,
                'status'      => 'active',
                'invited_by'  => $invitation->invited_by,
                'accepted_at' => now(),
            ]);

            // 그룹 접근 권한 부여
            foreach ($invitation->company_group_ids ?? [] as $groupId) {
                AdminCompanyGroupAccess::create([
                    'admin_user_id'    => $admin->id,
                    'company_group_id' => $groupId,
                    'can_manage_users' => false,
                    'can_view_chats'   => true,
                ]);
            }

            $invitation->update(['status' => 'accepted']);
        });

        return view('admin.invite-done');
    }
}
