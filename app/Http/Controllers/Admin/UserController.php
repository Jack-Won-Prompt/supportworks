<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\CompanyGroup;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $admin    = auth('admin')->user();
        $groups   = $this->accessibleGroups($admin);
        $groupIds = $groups->pluck('id');

        $users = User::with('companyGroup')
            ->when($request->search, fn($q) =>
                $q->where(fn($inner) =>
                    $inner->where('name', 'like', '%'.$request->search.'%')
                          ->orWhere('email', 'like', '%'.$request->search.'%')
                )
            )
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->group_id, fn($q) => $q->where('company_group_id', $request->group_id))
            ->when(!$admin->isSuperAdmin(),
                fn($q) => $q->whereIn('company_group_id', $groupIds)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingInvitations = Invitation::with('companyGroup')
            ->whereNull('accepted_at')
            ->when(!$admin->isSuperAdmin(), fn($q) => $q->whereIn('company_group_id', $groupIds))
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'groups', 'pendingInvitations', 'projects'));
    }

    public function create()
    {
        $admin  = auth('admin')->user();
        $groups = $this->accessibleGroups($admin);
        return view('admin.users.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $admin = auth('admin')->user();

        $validated = $request->validateWithBag('createUser', [
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users',
            'password'         => 'required|min:8|confirmed',
            'role'             => 'required|in:admin,member,client',
            'company'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'company_group_id' => 'nullable|exists:company_groups,id',
        ]);

        // 비super_admin은 자신의 그룹에만 배정 가능
        if (!$admin->isSuperAdmin() && $validated['company_group_id']) {
            $admin->load('companyGroups');
            if (!$admin->companyGroups->contains('id', $validated['company_group_id'])) {
                return back()->withErrors(['company_group_id' => '접근 권한이 없는 그룹입니다.'], 'createUser')->withInput();
            }
        }

        User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', '사용자가 생성되었습니다.');
    }

    public function edit(User $user)
    {
        $admin  = auth('admin')->user();
        $groups = $this->accessibleGroups($admin);
        return view('admin.users.edit', compact('user', 'groups'));
    }

    public function update(Request $request, User $user)
    {
        $admin = auth('admin')->user();

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email,'.$user->id,
            'role'             => 'required|in:admin,member,client',
            'company'          => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'password'         => 'nullable|min:8|confirmed',
            'company_group_id' => 'nullable|exists:company_groups,id',
        ]);

        if (!$admin->isSuperAdmin() && $validated['company_group_id']) {
            $admin->load('companyGroups');
            if (!$admin->companyGroups->contains('id', $validated['company_group_id'])) {
                if ($request->expectsJson()) {
                    return response()->json(['errors' => ['company_group_id' => ['접근 권한이 없는 그룹입니다.']]], 422);
                }
                return back()->withErrors(['company_group_id' => '접근 권한이 없는 그룹입니다.'])->withInput();
            }
        }

        if ($validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'ok'   => true,
                'user' => [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'role'    => $user->role,
                    'company' => $user->company ?? '',
                ],
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', '사용자가 수정되었습니다.');
    }

    public function invite(Request $request)
    {
        $admin = auth('admin')->user();

        $validated = $request->validate([
            'email'            => 'required|email|max:255',
            'phone'            => 'nullable|string|max:30',
            'company_group_id' => 'required|exists:company_groups,id',
            'message'          => 'nullable|string|max:500',
            'project_ids'      => 'nullable|array',
            'project_ids.*'    => 'exists:projects,id',
        ]);

        $invitePhone = $request->filled('phone')
            ? preg_replace('/[^\d+\-\s]/', '', trim($validated['phone']))
            : null;

        if (!$admin->isSuperAdmin()) {
            $admin->load('companyGroups');
            if (!$admin->companyGroups->contains('id', $validated['company_group_id'])) {
                return back()->withErrors(['company_group_id' => '접근 권한이 없는 그룹입니다.'])->withInput();
            }
        }

        if (User::where('email', $validated['email'])->exists()) {
            return back()->withErrors(['invite_email' => '이미 가입된 이메일입니다.'])->withInput();
        }

        $invitation = Invitation::where('email', $validated['email'])->whereNull('accepted_at')->first();

        if (!$invitation) {
            $invitation = Invitation::create([
                'email'            => $validated['email'],
                'phone'            => $invitePhone,
                'message'          => $validated['message'] ?? null,
                'project_ids'      => !empty($validated['project_ids']) ? array_map('intval', $validated['project_ids']) : null,
                'token'            => Str::random(40),
                'invited_by'       => null,
                'company_group_id' => $validated['company_group_id'],
            ]);
        } else {
            $invitation->update([
                'phone'            => $invitePhone ?: $invitation->phone,
                'message'          => $validated['message'] ?? null,
                'project_ids'      => !empty($validated['project_ids']) ? array_map('intval', $validated['project_ids']) : null,
                'company_group_id' => $validated['company_group_id'],
            ]);
        }

        $invitedProjects = !empty($validated['project_ids'])
            ? Project::whereIn('id', $validated['project_ids'])->pluck('name')->all()
            : [];

        try {
            Mail::to($validated['email'])->send(new InvitationMail($invitation, $invitedProjects, $admin->name));
            return back()->with('success', "{$validated['email']} 으로 초대 이메일이 발송되었습니다.");
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e);
            \Log::error('[AdminInvite] ' . $e->getMessage());
            $inviteUrl = rtrim(config('app.url'), '/') . route('team.accept', $invitation->token, false);
            return back()
                ->with('invite_link', $inviteUrl)
                ->with('invite_link_email', $validated['email'])
                ->with('mail_error', '이메일 발송 실패. 아래 링크를 직접 공유하세요. (' . $e->getMessage() . ')');
        }
    }

    public function cancelInvite(Invitation $invitation)
    {
        $admin = auth('admin')->user();

        if (!$admin->isSuperAdmin()) {
            $admin->load('companyGroups');
            if (!$admin->companyGroups->contains('id', $invitation->company_group_id)) {
                abort(403);
            }
        }

        $invitation->delete();
        return back()->with('success', '초대가 취소되었습니다.');
    }

    public function updateGroup(Request $request, User $user)
    {
        $admin = auth('admin')->user();

        $validated = $request->validate([
            'company_group_id' => 'nullable|exists:company_groups,id',
        ]);

        if (!$admin->isSuperAdmin()) {
            $admin->load('companyGroups');
            $accessibleIds = $admin->companyGroups->pluck('id');
            // 현재 소속 그룹 또는 변경 대상 그룹 모두 접근 가능해야 함
            $targetId = $validated['company_group_id'];
            if (!$accessibleIds->contains($user->company_group_id) &&
                !($targetId && $accessibleIds->contains($targetId))) {
                return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
            }
        }

        $user->update(['company_group_id' => $validated['company_group_id'] ?: null]);

        return response()->json(['ok' => true]);
    }

    public function impersonate(User $user)
    {
        $token = Str::random(40);
        Cache::put("impersonate_{$token}", $user->id, 60); // 60초 일회성 토큰

        return response()->json([
            'url' => route('impersonate.login', $token),
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', '본인 계정은 삭제할 수 없습니다.');
        }
        $user->delete();
        return back()->with('success', '사용자가 삭제되었습니다.');
    }

    private function accessibleGroups($admin)
    {
        return $admin->isSuperAdmin()
            ? CompanyGroup::orderBy('name')->get(['id', 'name'])
            : $admin->companyGroups()->orderBy('name')->get(['company_groups.id', 'company_groups.name']);
    }
}
