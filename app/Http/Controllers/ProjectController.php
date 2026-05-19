<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $viewAll = $request->boolean('all') && !$user->isAdmin();

        if ($viewAll) {
            $query = Project::companyOf($user);
        } else {
            $query = $user->isAdmin()
                ? Project::companyOf($user)
                : Project::where(fn($q) =>
                    $q->whereHas('projectMembers', fn($q2) => $q2->where('user_id', $user->id))
                      ->orWhere('created_by', $user->id)
                );
        }

        if ($request->status) $query->where('status', $request->status);
        if ($request->search) $query->where('name', 'like', '%'.$request->search.'%');

        $projects = $query->with(['creator',
                'myMembership' => fn($q) => $q->where('user_id', $user->id),
            ])
            ->withCount(['schedules', 'questions', 'files'])
            ->latest()->paginate(12);

        return view('projects.index', compact('projects', 'viewAll'));
    }

    public function join(Project $project)
    {
        $user = auth()->user();
        abort_if($user->isAdmin(), 403);

        // 생성자이면 항상 허용, 그 외엔 같은 회사 그룹 프로젝트에만 허용
        $allowed = $project->created_by === $user->id
            || ($user->company_group_id && (int)$project->company_group_id === (int)$user->company_group_id);

        abort_unless($allowed, 403, '같은 회사 프로젝트에만 참여할 수 있습니다.');

        if (!$project->isMember($user)) {
            ProjectMember::create([
                'project_id' => $project->id,
                'user_id'    => $user->id,
                'role'       => 'member',
            ]);
        }

        return response()->json([
            'ok'       => true,
            'joined'   => true,
            'project'  => [
                'id'       => $project->id,
                'name'     => $project->name,
                'status'   => $project->status,
                'show_url' => route('projects.show', $project),
            ],
        ]);
    }

    public function leave(Project $project)
    {
        $user = auth()->user();
        abort_if($user->isAdmin(), 403);

        $project->projectMembers()->where('user_id', $user->id)->delete();

        return response()->json(['ok' => true, 'joined' => false]);
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        $project = Project::create([
            ...$validated,
            'created_by'       => auth()->id(),
            'company_group_id' => auth()->user()->company_group_id,
        ]);

        // 생성자를 매니저로 추가
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'role' => 'manager',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'redirect' => route('projects.show', $project),
            ]);
        }

        return redirect()->route('projects.show', $project)
            ->with('success', '프로젝트가 생성되었습니다.');
    }

    public function show(Project $project)
    {
        $this->authorizeProject($project);

        $project->load([
            'creator',
            'projectMembers.user',
            'planningDocs' => fn($q) => $q->latest()->take(4)->with('creator'),
            'schedules' => fn($q) => $q->latest()->take(5)->with('assignee'),
            'questions' => fn($q) => $q->latest()->take(5)->with('user'),
            'files' => fn($q) => $q->latest()->take(5)->with('uploader')->withCount('comments'),
        ]);

        $scheduleStats = [
            'total' => $project->schedules()->count(),
            'completed' => $project->schedules()->where('status', 'completed')->count(),
            'in_progress' => $project->schedules()->where('status', 'in_progress')->count(),
        ];

        $user      = auth()->user();
        $isManager = $user->isAdmin() || $project->getMemberRole($user) === 'manager';

        return view('projects.show', compact('project', 'scheduleStats', 'isManager'));
    }

    public function edit(Project $project)
    {
        $this->authorizeProject($project, 'manager');
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProject($project, 'manager');

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'status'           => 'required|in:active,on_hold,completed,cancelled',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'client_name'      => 'nullable|string|max:255',
            'client_email'     => 'nullable|email|max:255',
            'si_mode_enabled'        => 'boolean',
            'sm_mode_enabled'        => 'boolean',
            'preferred_llm_provider' => 'nullable|in:anthropic,openai',
            'preferred_llm_model'    => 'nullable|string|max:100',
        ]);

        $validated['si_mode_enabled'] = $request->boolean('si_mode_enabled');
        $validated['sm_mode_enabled'] = $request->boolean('sm_mode_enabled');
        $project->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.show', $project)
            ->with('success', '프로젝트가 수정되었습니다.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeProject($project, 'manager');
        $project->delete();
        return redirect()->route('projects.index')
            ->with('success', '프로젝트가 삭제되었습니다.');
    }

    private function authorizeProject(Project $project, string $requiredRole = null): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');

        if ($requiredRole === 'manager' && $member->role !== 'manager') {
            abort(403, '매니저 권한이 필요합니다.');
        }
    }
}
