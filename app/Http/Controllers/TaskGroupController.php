<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskGroupController extends Controller
{
    private function authorizeProject(Project $project): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) return;

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        abort_unless($isMember || $project->created_by === $user->id, 403);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'milestone_id'  => 'nullable|exists:milestones,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $data['project_id'] = $project->id;

        if (!isset($data['display_order'])) {
            $data['display_order'] = TaskGroup::where('project_id', $project->id)->max('display_order') + 1;
        }

        $group = TaskGroup::create($data);

        return response()->json($group->load('subTasks'), 201);
    }

    public function update(Request $request, Project $project, TaskGroup $taskGroup)
    {
        $this->authorizeProject($project);
        abort_unless($taskGroup->project_id === $project->id, 404);

        $data = $request->validate([
            'milestone_id'  => 'nullable|exists:milestones,id',
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $taskGroup->update($data);

        return response()->json($taskGroup);
    }

    public function destroy(Project $project, TaskGroup $taskGroup)
    {
        $this->authorizeProject($project);
        abort_unless($taskGroup->project_id === $project->id, 404);

        $taskGroup->delete();

        return response()->json(['ok' => true]);
    }

    public function move(Request $request, Project $project, TaskGroup $taskGroup)
    {
        $this->authorizeProject($project);
        abort_unless($taskGroup->project_id === $project->id, 404);

        $data = $request->validate([
            'milestone_id' => 'nullable|exists:milestones,id',
        ]);

        $taskGroup->update(['milestone_id' => $data['milestone_id']]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];

        foreach ($ids as $idx => $id) {
            TaskGroup::where('id', $id)->where('project_id', $project->id)
                ->update(['display_order' => $idx]);
        }

        return response()->json(['ok' => true]);
    }
}
