<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->isAdmin()
            ? Project::companyOf($user)
            : Project::whereHas('projectMembers', fn($q) => $q->where('user_id', $user->id))
                ->orWhere('created_by', $user->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $projects = $query->with('creator')
            ->withCount(['schedules', 'questions', 'files'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data'  => $projects->map(fn($p) => $this->projectResource($p)),
            'meta'  => [
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'total'        => $projects->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'status'       => 'required|in:active,on_hold,completed,cancelled',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'client_name'  => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        $project = Project::create([
            ...$validated,
            'created_by'       => $request->user()->id,
            'company_group_id' => $request->user()->company_group_id,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $request->user()->id,
            'role'       => 'manager',
        ]);

        $project->load('creator');

        return response()->json($this->projectResource($project), 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $project->load([
            'creator',
            'projectMembers.user',
            'schedules' => fn($q) => $q->latest()->take(5)->with('assignee'),
            'questions' => fn($q) => $q->latest()->take(5)->with('user'),
            'files'     => fn($q) => $q->latest()->take(5)->with('uploader'),
        ]);

        $scheduleStats = [
            'total'       => $project->schedules()->count(),
            'completed'   => $project->schedules()->where('status', 'completed')->count(),
            'in_progress' => $project->schedules()->where('status', 'in_progress')->count(),
        ];

        return response()->json([
            ...$this->projectResource($project),
            'schedule_stats' => $scheduleStats,
            'members'        => $project->projectMembers->map(fn($m) => [
                'id'   => $m->id,
                'role' => $m->role,
                'user' => ['id' => $m->user->id, 'name' => $m->user->name, 'email' => $m->user->email],
            ]),
            'recent_schedules' => $project->schedules->map(fn($s) => [
                'id'         => $s->id,
                'title'      => $s->title,
                'status'     => $s->status,
                'start_date' => $s->start_date,
                'end_date'   => $s->end_date,
            ]),
            'recent_questions' => $project->questions->map(fn($q) => [
                'id'      => $q->id,
                'title'   => $q->title,
                'status'  => $q->status,
                'user'    => ['id' => $q->user->id, 'name' => $q->user->name],
            ]),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project, 'manager');

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'status'       => 'required|in:active,on_hold,completed,cancelled',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'client_name'  => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
        ]);

        $project->update($validated);
        $project->load('creator');

        return response()->json($this->projectResource($project));
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project, 'manager');
        $project->delete();
        return response()->json(['message' => '프로젝트가 삭제되었습니다.']);
    }

    private function projectResource(Project $project): array
    {
        return [
            'id'              => $project->id,
            'name'            => $project->name,
            'description'     => $project->description,
            'status'          => $project->status,
            'start_date'      => $project->start_date,
            'end_date'        => $project->end_date,
            'client_name'     => $project->client_name,
            'client_email'    => $project->client_email,
            'created_at'      => $project->created_at,
            'schedules_count' => $project->schedules_count ?? null,
            'questions_count' => $project->questions_count ?? null,
            'files_count'     => $project->files_count ?? null,
            'creator'         => $project->creator ? [
                'id'   => $project->creator->id,
                'name' => $project->creator->name,
            ] : null,
        ];
    }

    private function authorizeProject($user, Project $project, string $requiredRole = null): void
    {
        if ($user->isAdmin()) return;

        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');

        if ($requiredRole === 'manager' && $member->role !== 'manager') {
            abort(403, '매니저 권한이 필요합니다.');
        }
    }
}