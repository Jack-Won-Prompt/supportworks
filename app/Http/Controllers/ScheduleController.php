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
            ->with(['taskGroup', 'assignee', 'assignees:id,name', 'files'])
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
                '_assignee'     => $t->assignees->isNotEmpty()
                    ? $t->assignees->pluck('name')->implode(', ')
                    : ($t->assignee?->name ?? '미배정'),
                '_assignee_id'  => $t->assignee_id,
                '_assignee_ids' => $t->assignees->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
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
            'start_date'     => 'sometimes|date',
            'end_date'       => 'sometimes|nullable|date',
            'status'         => 'sometimes|in:not_started,in_progress,completed,blocked,on_hold',
            'assignee_id'    => 'sometimes|nullable|exists:users,id',
            'assignee_ids'   => 'sometimes|nullable|array',
            'assignee_ids.*' => 'integer|exists:users,id',
            'progress'       => 'sometimes|integer|min:0|max:100',
            'reason'         => 'sometimes|nullable|string|max:1000',
        ]);

        // 상태 변경 시 이력 기록 (실제 변경된 경우에만)
        $oldStatus = $subTask->status;
        $newStatus = $validated['status'] ?? null;
        $reason    = $validated['reason'] ?? null;
        unset($validated['reason']);

        // '완료' 상태로 한 번 변경되면 다시는 다른 상태로 못 바꿈
        if ($oldStatus === 'completed' && $newStatus !== null && $newStatus !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => '완료된 일정은 상태를 변경할 수 없습니다.',
            ], 422);
        }

        // 다중 담당자 처리 — 피벗 sync + 대표(assignee_id) 자동 동기화
        $newlyAssignedIds = [];
        if ($request->has('assignee_ids')) {
            $ids = array_values(array_unique(array_map('intval', (array) $request->input('assignee_ids', []))));
            $beforeIds = $subTask->assignees()->pluck('users.id')->map(fn ($v) => (int) $v)->all();
            $newlyAssignedIds = array_values(array_diff($ids, $beforeIds));
            $subTask->assignees()->sync($ids);
            $validated['assignee_id'] = $ids[0] ?? null;
            unset($validated['assignee_ids']);
        }

        $subTask->update($validated);

        if ($newStatus && $newStatus !== $oldStatus) {
            \App\Models\SubTaskStatusLog::create([
                'sub_task_id' => $subTask->id,
                'user_id'     => auth()->id(),
                'old_status'  => $oldStatus,
                'new_status'  => $newStatus,
                'reason'      => $reason,
            ]);
        }

        // 신규 지정된 담당자에게 알림 (이메일 + SMS)
        if (!empty($newlyAssignedIds)) {
            $this->notifyAssignedUsers($newlyAssignedIds, $project, $subTask->refresh());
        }

        return response()->json(['success' => true]);
    }

    /**
     * 일정 담당자 지정 알림 — 이메일 + (휴대폰 번호 있으면) SMS.
     *
     * @param  array<int,int>  $userIds  신규 지정된 사용자 ID 목록
     */
    private function notifyAssignedUsers(array $userIds, Project $project, SubTask $subTask): void
    {
        if (empty($userIds)) return;

        $assigner    = auth()->user();
        $statusLabel = [
            'not_started'    => '미시작',
            'in_progress'    => '진행중',
            'completed'      => '완료',
            'blocked'        => '제외',
            'on_hold'        => '보류',
        ][$subTask->status] ?? (string) $subTask->status;

        $recipients = \App\Models\User::whereIn('id', $userIds)->get();
        foreach ($recipients as $user) {
            if ($assigner && (int) $user->id === (int) $assigner->id) continue;   // 본인은 알림 안 함

            // 이메일
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email, $user->name)
                        ->send(new \App\Mail\SubTaskAssignedMail($user, $project, $subTask, $assigner, $statusLabel));
                } catch (\Throwable $e) {
                    \App\Models\SystemErrorLog::record($e, 'warning');
                }
            }

            // SMS (휴대폰 번호 있을 때만)
            if (!empty($user->phone)) {
                $msg = sprintf(
                    "[SupportWorks] %s님이 [%s] 프로젝트 일정 '%s'(%s)의 담당자로 지정했습니다.",
                    $assigner?->name ?: '시스템',
                    $project->name,
                    mb_strimwidth($subTask->title, 0, 24, '...', 'UTF-8'),
                    $statusLabel,
                );
                try { \App\Services\SmsService::send($user->phone, $msg, $user->name); } catch (\Throwable) {}
            }
        }
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
