<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $schedules = Schedule::where('project_id', $project->id)
            ->with('assignee')
            ->orderBy('sort_order')
            ->orderBy('start_date')
            ->get();

        return response()->json($schedules->map(fn($s) => $this->scheduleResource($s)));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'group_name'  => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'nullable|in:pending,in_progress,completed,on_hold',
            'priority'    => 'nullable|in:high,medium,low',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $maxOrder = Schedule::where('project_id', $project->id)->max('sort_order') ?? 0;

        $schedule = Schedule::create([
            ...$validated,
            'project_id'  => $project->id,
            'created_by'  => $request->user()->id,
            'sort_order'  => $maxOrder + 1,
            'status'      => $validated['status'] ?? 'pending',
        ]);

        $schedule->load('assignee');

        return response()->json($this->scheduleResource($schedule), 201);
    }

    public function show(Request $request, Schedule $schedule): JsonResponse
    {
        $schedule->load(['project', 'assignee', 'creator']);
        return response()->json($this->scheduleResource($schedule));
    }

    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $this->authorizeProject($request->user(), $schedule->project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'group_name'  => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'nullable|in:pending,in_progress,completed,on_hold',
            'priority'    => 'nullable|in:high,medium,low',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $schedule->update($validated);
        $schedule->load('assignee');

        return response()->json($this->scheduleResource($schedule));
    }

    public function destroy(Request $request, Schedule $schedule): JsonResponse
    {
        $this->authorizeProject($request->user(), $schedule->project);
        $schedule->delete();
        return response()->json(['message' => '일정이 삭제되었습니다.']);
    }

    private function scheduleResource(Schedule $schedule): array
    {
        return [
            'id'          => $schedule->id,
            'project_id'  => $schedule->project_id,
            'title'       => $schedule->title,
            'group_name'  => $schedule->group_name,
            'description' => $schedule->description,
            'start_date'  => $schedule->start_date,
            'end_date'    => $schedule->end_date,
            'status'      => $schedule->status,
            'priority'    => $schedule->priority,
            'sort_order'  => $schedule->sort_order,
            'assignee'    => $schedule->assignee ? [
                'id'   => $schedule->assignee->id,
                'name' => $schedule->assignee->name,
            ] : null,
            'created_at'  => $schedule->created_at,
        ];
    }

    private function authorizeProject($user, Project $project): void
    {
        if ($user->isAdmin()) return;
        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');
    }
}