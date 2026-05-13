<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MilestoneController extends Controller
{
    private function authorizeProject(Project $project): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) return;

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        abort_unless($isMember || $project->created_by === $user->id, 403);
    }

    public function index(Project $project)
    {
        $this->authorizeProject($project);

        $milestones = $project->milestones()
            ->withCount('taskGroups')
            ->with(['taskGroups.subTasks'])
            ->get()
            ->map(function ($m) {
                $allTasks = $m->taskGroups->flatMap->subTasks;
                $m->total_tasks    = $allTasks->count();
                $m->completed_tasks = $allTasks->where('status', 'completed')->count();
                $m->progress       = $m->total_tasks > 0
                    ? (int) $allTasks->avg('progress')
                    : 0;
                return $m;
            });

        return response()->json($milestones);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'target_date'   => 'nullable|date',
            'status'        => 'nullable|in:planned,in_progress,completed,cancelled',
            'display_order' => 'nullable|integer',
        ]);

        if (!isset($data['display_order'])) {
            $data['display_order'] = $project->milestones()->max('display_order') + 1;
        }

        $milestone = $project->milestones()->create($data);

        return response()->json($milestone, 201);
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $this->authorizeProject($project);
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'target_date'   => 'nullable|date',
            'status'        => 'nullable|in:planned,in_progress,completed,cancelled',
            'display_order' => 'nullable|integer',
        ]);

        $milestone->update($data);

        return response()->json($milestone);
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $this->authorizeProject($project);
        abort_unless($milestone->project_id === $project->id, 404);

        $milestone->delete();

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];

        foreach ($ids as $idx => $id) {
            Milestone::where('id', $id)->where('project_id', $project->id)
                ->update(['display_order' => $idx]);
        }

        return response()->json(['ok' => true]);
    }
}
