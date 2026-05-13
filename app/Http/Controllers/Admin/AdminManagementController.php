<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminInvitationMail;
use App\Models\AdminInvitation;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = AdminUser::with('projects:id,name')
            ->orderByRaw("FIELD(role,'super_admin','admin','operator','support_agent')")
            ->orderBy('name')
            ->get();

        $pendingInvitations = AdminInvitation::with('invitedBy:id,name')
            ->where('status', 'invited')
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::orderBy('name')->get(['id', 'name', 'status']);

        return view('admin.management.index', compact('admins', 'pendingInvitations', 'projects'));
    }

    public function invite(Request $request)
    {
        abort_unless(auth('admin')->user()->isSuperAdmin(), 403);

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => ['required', 'email', 'unique:admin_users,email',
                        'unique:admin_invitations,email'],
            'role'  => 'required|in:admin,operator,support_agent',
        ], [
            'email.unique' => '이미 등록되었거나 초대 중인 이메일입니다.',
        ]);

        ['invitation' => $inv, 'raw_token' => $raw] = AdminInvitation::createInvitation(
            auth('admin')->user(),
            $request->only('name', 'email', 'role')
        );

        try {
            Mail::to($inv->email)->send(new AdminInvitationMail($inv, $raw));
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e);
            \Log::error('Admin invite mail: ' . $e->getMessage());
        }

        return back()->with('success', "{$inv->name}님({$inv->email})에게 초대 메일을 발송했습니다.");
    }

    public function cancelInvite(AdminInvitation $invitation)
    {
        abort_unless(auth('admin')->user()->isSuperAdmin(), 403);

        $invitation->update(['status' => 'disabled']);

        return back()->with('success', '초대가 취소되었습니다.');
    }

    public function assignProjects(Request $request, AdminUser $admin)
    {
        abort_unless(auth('admin')->user()->isSuperAdmin(), 403);

        $request->validate([
            'project_ids'   => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $admin->projects()->sync($request->project_ids ?? []);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'count' => count($request->project_ids ?? [])]);
        }

        return back()->with('success', '프로젝트가 배정되었습니다.');
    }
}
