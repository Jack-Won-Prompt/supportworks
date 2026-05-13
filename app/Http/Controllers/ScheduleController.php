<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Schedule;
use App\Models\SubTask;
use App\Services\ProjectNotificationService;
use App\Services\Schedule\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $service = app(ScheduleService::class);
        $tree    = $service->getTree($project->id);
        $members = $project->members()->get();

        return view('schedules.index', compact('project', 'tree', 'members'));
    }

    public function create(Project $project)
    {
        $this->authorizeProject($project);
        $members    = $project->members()->get();
        $groupNames = $project->schedules()->whereNotNull('group_name')->distinct()->pluck('group_name');
        return view('schedules.create', compact('project', 'members', 'groupNames'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'group_name'  => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:pending,in_progress,completed,cancelled,review_submitted,review_completed',
            'priority'    => 'required|in:low,medium,high',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $schedule = $project->schedules()->create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'schedule_created',
            $schedule->title,
            route('schedules.show', $schedule),
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $schedule->id]);
        }

        return redirect()->route('projects.schedules.index', $project)
            ->with('success', '일정이 등록되었습니다.');
    }

    public function show(Schedule $schedule)
    {
        $this->authorizeProject($schedule->project);
        $schedule->load(['project', 'assignee', 'creator', 'comments.user']);
        return view('schedules.show', compact('schedule'));
    }

    public function edit(Schedule $schedule)
    {
        $this->authorizeProject($schedule->project);
        $members    = $schedule->project->members()->get();
        $groupNames = $schedule->project->schedules()->whereNotNull('group_name')->distinct()->pluck('group_name');
        return view('schedules.edit', compact('schedule', 'members', 'groupNames'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $this->authorizeProject($schedule->project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'group_name'  => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:pending,in_progress,completed,cancelled,review_submitted,review_completed',
            'priority'    => 'required|in:low,medium,high',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $schedule->update($validated);

        app(ProjectNotificationService::class)->notify(
            $schedule->project, auth()->user(), 'schedule_updated',
            $schedule->title,
            route('schedules.show', $schedule),
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.gantt', $schedule->project)
            ->with('success', '일정이 수정되었습니다.');
    }

    public function destroy(Schedule $schedule)
    {
        $this->authorizeProject($schedule->project);
        $project = $schedule->project;
        $title   = $schedule->title;
        $schedule->delete();

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'schedule_deleted',
            $title,
            route('projects.schedules.index', $project),
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.schedules.index', $project)
            ->with('success', '일정이 삭제되었습니다.');
    }

    public function gantt(Project $project)
    {
        $this->authorizeProject($project);

        $service  = app(ScheduleService::class);
        $members  = $project->members()->get();

        $subTasks = SubTask::where('project_id', $project->id)
            ->with(['taskGroup', 'assignee', 'files'])
            ->orderBy('task_group_id')
            ->orderBy('display_order')
            ->get();

        $groupNames = $subTasks->map(fn($t) => $t->taskGroup?->title ?? '미분류')
            ->filter()->unique()->values();

        $ganttTasks = $subTasks->map(function (SubTask $t) use ($project) {
            $start = $t->start_date?->format('Y-m-d') ?? now()->toDateString();
            $end   = $t->end_date?->format('Y-m-d') ?? now()->toDateString();

            return [
                'id'            => (string) $t->id,
                'name'          => $t->title,
                'group_name'    => $t->taskGroup?->title ?? '미분류',
                'start'         => $start,
                'end'           => $end,
                'progress'      => $t->progress,
                '_status'       => $t->status,
                '_status_label' => $t->status_label,
                '_priority'     => '',
                '_assignee'     => $t->assignee?->name ?? '미배정',
                '_assignee_id'  => $t->assignee_id,
                '_show_url'     => route('projects.sub-tasks.show', [$project, $t]),
                '_delete_url'   => route('projects.sub-tasks.destroy', [$project, $t]),
                '_description'  => $t->description ?? '',
                '_start_dt'     => $start,
                '_end_dt'       => $end,
                '_priority_val' => '',
                '_group_raw'    => $t->taskGroup?->title ?? '',
                '_files_count'  => $t->files->count(),
                '_files_url'    => '',
                '_files'        => $t->files->map(fn($f) => [
                    'id'           => $f->id,
                    'name'         => $f->original_name,
                    'size'         => $f->size,
                    'preview_type' => $f->previewType(),
                ])->toArray(),
                '_source_type'  => $t->source_type,
            ];
        })->values();

        // Keep $schedules as empty collection for view compatibility
        $schedules = collect();

        return view('projects.gantt', compact('project', 'schedules', 'ganttTasks', 'groupNames', 'members'));
    }

    public function ganttUpdate(Request $request, Project $project, SubTask $subTask)
    {
        $this->authorizeProject($project);
        abort_unless($subTask->project_id === $project->id, 403);

        $validated = $request->validate([
            'start_date'  => 'sometimes|date',
            'end_date'    => 'sometimes|nullable|date',
            'status'      => 'sometimes|in:not_started,in_progress,completed,blocked',
            'assignee_id' => 'sometimes|nullable|exists:users,id',
            'progress'    => 'sometimes|integer|min:0|max:100',
        ]);

        $subTask->update($validated);

        return response()->json(['success' => true]);
    }

    public function ganttReorder(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $request->validate([
            'order'                  => 'required|array',
            'order.*.id'             => 'required|integer',
            'order.*.sort_order'     => 'required|integer',
            'order.*.task_group_id'  => 'nullable|integer',
        ]);

        foreach ($request->order as $item) {
            SubTask::where('id', $item['id'])
                ->where('project_id', $project->id)
                ->update([
                    'display_order' => $item['sort_order'],
                    'task_group_id' => $item['task_group_id'] ?? null,
                ]);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }
}
