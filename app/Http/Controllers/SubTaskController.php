<?php

namespace App\Http\Controllers;

use App\Models\ItemChangeHistory;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\SubTask;
use App\Models\TaskGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubTaskController extends Controller
{
    private function authorizeProject(Project $project): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) return;

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        abort_unless($isMember || $project->created_by === $user->id, 403);
    }

    public function index(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $query = SubTask::where('project_id', $project->id)->with(['taskGroup', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('task_group_id')) {
            $query->where('task_group_id', $request->task_group_id);
        }
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        $subTasks = $query->orderBy('task_group_id')->orderBy('display_order')->get();

        return response()->json($subTasks);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'task_group_id'  => 'required|exists:task_groups,id',
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'assignee_id'    => 'nullable|exists:users,id',
            'status'         => 'nullable|in:not_started,in_progress,completed,blocked',
            'progress'       => 'nullable|integer|min:0|max:100',
            'display_order'  => 'nullable|integer',
            'requirement_id' => 'nullable|integer|exists:requirements,id',
        ]);

        // 같은 요구사항이 이미 간트에 등록된 경우 거부
        if (!empty($data['requirement_id'])) {
            $already = SubTask::where('project_id', $project->id)
                ->where('requirement_id', $data['requirement_id'])
                ->whereNull('deleted_at')
                ->exists();
            if ($already) {
                return response()->json(['message' => '이미 간트에 등록된 요구사항입니다.'], 422);
            }
        }

        $data['project_id']  = $project->id;
        $data['source_type'] = 'manual';

        if (!isset($data['display_order'])) {
            $data['display_order'] = SubTask::where('task_group_id', $data['task_group_id'])->max('display_order') + 1;
        }

        $subTask = SubTask::create($data);

        // 연결된 요구사항 상태를 '확정'으로 즉시 변경
        if (!empty($data['requirement_id'])) {
            $req = Requirement::find($data['requirement_id']);
            if ($req && $req->status !== 'confirmed') {
                $old = $req->status;
                $req->update(['status' => 'confirmed']);
                ItemChangeHistory::create([
                    'item_type'     => Requirement::class,
                    'item_id'       => $req->id,
                    'changed_by_id' => Auth::id(),
                    'changed_at'    => now(),
                    'field_name'    => 'status',
                    'old_value'     => $old,
                    'new_value'     => 'confirmed',
                ]);
            }
        }

        return response()->json($subTask->load('assignee'), 201);
    }

    public function show(Project $project, SubTask $subTask)
    {
        $this->authorizeProject($project);
        abort_unless($subTask->project_id === $project->id, 404);

        return response()->json($subTask->load(['taskGroup.milestone', 'assignee']));
    }

    public function update(Request $request, Project $project, SubTask $subTask)
    {
        $this->authorizeProject($project);
        abort_unless($subTask->project_id === $project->id, 404);

        $data = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'start_date'    => 'sometimes|date',
            'end_date'      => 'sometimes|date|after_or_equal:start_date',
            'assignee_id'   => 'nullable|exists:users,id',
            'status'        => 'nullable|in:not_started,in_progress,completed,blocked',
            'progress'      => 'nullable|integer|min:0|max:100',
            'display_order' => 'nullable|integer',
        ]);

        $subTask->update($data);

        return response()->json($subTask->load('assignee'));
    }

    public function destroy(Project $project, SubTask $subTask)
    {
        $this->authorizeProject($project);
        abort_unless($subTask->project_id === $project->id, 404);

        $subTask->delete();

        return response()->json(['ok' => true]);
    }

    public function move(Request $request, Project $project, SubTask $subTask)
    {
        $this->authorizeProject($project);
        abort_unless($subTask->project_id === $project->id, 404);

        $data = $request->validate([
            'task_group_id' => 'required|exists:task_groups,id',
        ]);

        $group = TaskGroup::findOrFail($data['task_group_id']);
        abort_unless($group->project_id === $project->id, 422);

        $subTask->update([
            'task_group_id' => $group->id,
            'display_order' => SubTask::where('task_group_id', $group->id)->max('display_order') + 1,
        ]);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'ids'           => 'required|array',
            'ids.*'         => 'integer',
            'task_group_id' => 'nullable|exists:task_groups,id',
        ]);

        foreach ($data['ids'] as $idx => $id) {
            $q = SubTask::where('id', $id)->where('project_id', $project->id);
            if (!empty($data['task_group_id'])) {
                $q->where('task_group_id', $data['task_group_id']);
            }
            $q->update(['display_order' => $idx]);
        }

        return response()->json(['ok' => true]);
    }
}
