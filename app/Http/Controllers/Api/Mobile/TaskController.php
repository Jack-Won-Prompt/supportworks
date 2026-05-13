<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $filter = $request->query('filter', 'all');

        $base = Task::where('user_id', $user->id)
            ->with('project')
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->orderBy('due_date')
            ->orderByDesc('created_at');

        if ($request->project_id) {
            $base->where('project_id', $request->project_id);
        }

        $tasks = match ($filter) {
            'todo'        => (clone $base)->where('status', 'todo')->get(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->get(),
            'done'        => Task::where('user_id', $user->id)->where('status', 'done')
                ->with('project')->orderByDesc('updated_at')->take(50)->get(),
            'overdue'     => (clone $base)->whereIn('status', ['todo', 'in_progress'])
                ->where('due_date', '<', today())->get(),
            default       => $base->get(),
        };

        $overdueCount = Task::where('user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->where('due_date', '<', today())
            ->count();

        return response()->json([
            'tasks'         => $tasks->map(fn($t) => $this->taskResource($t)),
            'overdue_count' => $overdueCount,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'priority'    => 'nullable|in:high,medium,low',
            'due_date'    => 'nullable|date',
            'project_id'  => 'nullable|exists:projects,id',
        ]);

        $task = Task::create([
            'user_id'     => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority ?? 'medium',
            'due_date'    => $request->due_date,
            'project_id'  => $request->project_id,
            'status'      => 'todo',
        ]);

        $task->load('project');

        return response()->json($this->taskResource($task), 201);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        abort_if($task->user_id !== $request->user()->id, 403);
        $request->validate(['status' => 'required|in:todo,in_progress,done']);
        $task->update(['status' => $request->status]);
        return response()->json($this->taskResource($task));
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        abort_if($task->user_id !== $request->user()->id, 403);
        $task->delete();
        return response()->json(['message' => '태스크가 삭제되었습니다.']);
    }

    private function taskResource(Task $task): array
    {
        return [
            'id'          => $task->id,
            'title'       => $task->title,
            'description' => $task->description,
            'status'      => $task->status,
            'priority'    => $task->priority,
            'due_date'    => $task->due_date,
            'created_at'  => $task->created_at,
            'project'     => $task->project ? [
                'id'   => $task->project->id,
                'name' => $task->project->name,
            ] : null,
        ];
    }
}
