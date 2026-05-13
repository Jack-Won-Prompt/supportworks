<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function index(Project $project)
    {
        $this->authorizeMember($project);
        $project->load('projectMembers.user');

        $availableUsers = collect();
        if (auth()->user()->isAdmin() || $project->getMemberRole(auth()->user()) === 'manager') {
            $existingIds    = $project->members()->pluck('users.id');
            $availableUsers = User::companyOf(auth()->user())
                ->whereNotIn('id', $existingIds)
                ->orderBy('name')
                ->get();
        }

        return view('members.index', compact('project', 'availableUsers'));
    }

    public function json(Project $project)
    {
        $this->authorizeMember($project);

        $availableUsers = collect();
        if (auth()->user()->isAdmin() || $project->getMemberRole(auth()->user()) === 'manager') {
            $existingIds    = $project->members()->pluck('users.id');
            $availableUsers = User::companyOf(auth()->user())
                ->whereNotIn('id', $existingIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $members = $project->projectMembers()->with('user:id,name,email')->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'user_id'    => $m->user_id,
                'name'       => $m->user->name,
                'email'      => $m->user->email,
                'role'       => $m->role,
                'role_label' => $m->role_label,
                'is_self'    => $m->user_id === auth()->id(),
            ]);

        return response()->json([
            'members'        => $members,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeManager($project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:manager,member,viewer',
        ]);

        $actor     = auth()->user();
        $newMember = User::findOrFail($validated['user_id']);

        if ($actor->hasCompany() && !$actor->inSameCompany($newMember)) {
            $msg = '같은 회사 구성원만 프로젝트에 추가할 수 있습니다.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        if ($project->projectMembers()->where('user_id', $newMember->id)->exists()) {
            $msg = '이미 프로젝트 멤버입니다.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $newMember->id,
            'role'       => $validated['role'],
        ]);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', '멤버가 추가되었습니다.');
    }

    public function bulkStore(Request $request, Project $project)
    {
        $this->authorizeManager($project);

        $validated = $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
            'role'       => 'required|in:manager,member,viewer',
        ]);

        $actor  = auth()->user();
        $added  = 0;
        $skipped = [];

        foreach ($validated['user_ids'] as $userId) {
            $newMember = User::findOrFail($userId);

            if ($actor->hasCompany() && !$actor->inSameCompany($newMember)) {
                $skipped[] = $newMember->name;
                continue;
            }

            if ($project->projectMembers()->where('user_id', $newMember->id)->exists()) {
                $skipped[] = $newMember->name;
                continue;
            }

            ProjectMember::create([
                'project_id' => $project->id,
                'user_id'    => $newMember->id,
                'role'       => $validated['role'],
            ]);
            $added++;
        }

        $msg = "{$added}명의 멤버가 추가되었습니다.";
        if ($skipped) {
            $msg .= ' (건너뜀: ' . implode(', ', $skipped) . ')';
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $msg, 'added' => $added])
            : back()->with('success', $msg);
    }

    public function update(Request $request, Project $project, ProjectMember $member)
    {
        $this->authorizeManager($project);

        $validated = $request->validate(['role' => 'required|in:manager,member,viewer']);

        if ($member->role === 'manager' && $validated['role'] !== 'manager') {
            $managerCount = $project->projectMembers()->where('role', 'manager')->count();
            if ($managerCount <= 1) {
                $msg = '프로젝트에 매니저가 최소 1명은 있어야 합니다.';
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : back()->with('error', $msg);
            }
        }

        $member->update($validated);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', '역할이 변경되었습니다.');
    }

    public function destroy(Project $project, ProjectMember $member)
    {
        $this->authorizeManager($project);

        if ($member->user_id === auth()->id()) {
            $msg = '본인은 제거할 수 없습니다.';
            return request()->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        if ($member->role === 'manager') {
            $managerCount = $project->projectMembers()->where('role', 'manager')->count();
            if ($managerCount <= 1) {
                $msg = '프로젝트에 매니저가 최소 1명은 있어야 합니다.';
                return request()->expectsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : back()->with('error', $msg);
            }
        }

        $member->delete();
        return request()->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', '멤버가 제거되었습니다.');
    }

    private function authorizeMember(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        $exists = $project->projectMembers()->where('user_id', $user->id)->exists();
        if (!$exists) {
            abort(403, '프로젝트 멤버만 접근할 수 있습니다.');
        }
    }

    private function authorizeManager(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member || $member->role !== 'manager') {
            abort(403, '매니저 권한이 필요합니다.');
        }
    }
}
