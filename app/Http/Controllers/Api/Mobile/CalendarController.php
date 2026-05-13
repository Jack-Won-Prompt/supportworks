<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ActionItem;
use App\Models\MeetingMinute;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $year  = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $projectIds = $user->isAdmin()
            ? Project::pluck('id')
            : $user->projects()->pluck('projects.id');

        $schedules = Schedule::whereIn('project_id', $projectIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->where('status', '!=', 'completed')
            ->with('project')
            ->get();

        $tasks = Task::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->with('project')
            ->get();

        $actionItems = ActionItem::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            })
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_completed', false)
            ->with('project')
            ->get();

        $meetings = MeetingMinute::companyOf($user)
            ->whereBetween('meeting_date', [$start->toDateString(), $end->toDateString()])
            ->with(['author', 'project'])
            ->get();

        return response()->json([
            'schedules'    => $schedules->map(fn($s) => [
                'id'         => $s->id,
                'type'       => 'schedule',
                'title'      => $s->title,
                'start_date' => $s->start_date,
                'end_date'   => $s->end_date,
                'status'     => $s->status,
                'project'    => $s->project ? ['id' => $s->project->id, 'name' => $s->project->name] : null,
            ]),
            'tasks'        => $tasks->map(fn($t) => [
                'id'       => $t->id,
                'type'     => 'task',
                'title'    => $t->title,
                'due_date' => $t->due_date,
                'status'   => $t->status,
                'priority' => $t->priority,
                'project'  => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
            ]),
            'action_items' => $actionItems->map(fn($a) => [
                'id'       => $a->id,
                'type'     => 'action_item',
                'title'    => $a->title,
                'due_date' => $a->due_date,
                'project'  => $a->project ? ['id' => $a->project->id, 'name' => $a->project->name] : null,
            ]),
            'meetings'     => $meetings->map(fn($m) => [
                'id'           => $m->id,
                'type'         => 'meeting',
                'title'        => $m->title,
                'meeting_date' => $m->meeting_date,
                'project'      => $m->project ? ['id' => $m->project->id, 'name' => $m->project->name] : null,
            ]),
        ]);
    }
}