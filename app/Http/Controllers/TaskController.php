<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $filter = $request->query('filter', 'all');

        $base = fn() => Task::where('user_id', $user->id)->with('project')
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->orderBy('due_date')
            ->orderByDesc('created_at');

        $projectId = $request->query('project_id');

        $applyFilter = function ($query) use ($filter, $projectId) {
            if ($projectId) {
                $query->where('project_id', $projectId);
            }
            return match ($filter) {
                'overdue'   => $query->where('due_date', '<', today()),
                'today'     => $query->whereDate('due_date', today()),
                'this_week' => $query->whereBetween('due_date', [today(), today()->addDays(6)]),
                'next_week' => $query->whereBetween('due_date', [today()->addDays(7), today()->addDays(13)]),
                default     => $query,
            };
        };

        $todo       = $applyFilter($base()->where('status', 'todo'))->get();
        $inProgress = $applyFilter($base()->where('status', 'in_progress'))->get();

        // 완료는 기간 필터 무관, 프로젝트 필터만 적용 — 최근 20개
        $doneQuery = Task::where('user_id', $user->id)
            ->where('status', 'done')
            ->with('project')
            ->orderByDesc('updated_at');
        if ($projectId) {
            $doneQuery->where('project_id', $projectId);
        }
        $done = $doneQuery->take(20)->get();

        // 지연 뱃지 카운트 (프로젝트 필터 반영)
        $overdueQuery = Task::where('user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->where('due_date', '<', today());
        if ($projectId) {
            $overdueQuery->where('project_id', $projectId);
        }
        $overdueCount = $overdueQuery->count();

        if ($user->isAdmin()) {
            $myProjects  = Project::companyOf($user)->orderBy('name')->get();
            $allProjects = collect();
        } else {
            $myProjectIds = $user->projects()->pluck('projects.id');
            $myProjects   = Project::whereIn('id', $myProjectIds)->orderBy('name')->get();
            $allProjects  = Project::companyOf($user)
                ->whereNotIn('id', $myProjectIds)
                ->orderBy('name')
                ->get();
        }

        $projects        = $myProjects;
        $selectedProject = $projectId ? $myProjects->firstWhere('id', (int) $projectId) : null;

        return view('tasks.index', compact(
            'todo', 'inProgress', 'done',
            'projects', 'allProjects', 'filter', 'overdueCount',
            'projectId', 'selectedProject'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'priority'    => 'nullable|in:high,medium,low',
            'due_date'    => 'nullable|date',
            'project_id'  => 'nullable|exists:projects,id',
        ]);

        Task::create([
            'user_id'     => auth()->id(),
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority ?? 'medium',
            'due_date'    => $request->due_date,
            'project_id'  => $request->project_id,
            'status'      => 'todo',
        ]);

        return back()->with('success', '태스크가 추가되었습니다.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        abort_if($task->user_id !== auth()->id(), 403);
        $request->validate(['status' => 'required|in:todo,in_progress,done']);
        $task->update(['status' => $request->status]);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back();
    }

    public function destroy(Task $task)
    {
        abort_if($task->user_id !== auth()->id(), 403);
        $task->delete();
        return back()->with('success', '태스크가 삭제되었습니다.');
    }
}
