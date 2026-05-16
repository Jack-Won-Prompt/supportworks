<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ActionItem;
use App\Models\Issue;
use App\Models\MeetingActionItem;
use App\Models\Task;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyWorkController extends Controller
{
    /** GET /my-work - 내 업무 통합 */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = today();

        // ── 내 태스크 ──────────────────────────────────────
        $myTasks = Task::where('user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->with('project:id,name')
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->orderBy('due_date')
            ->get();

        $overdueTasks    = $myTasks->filter(fn($t) => $t->due_date && Carbon::parse($t->due_date)->lt($today))->values();
        $dueTodayTasks   = $myTasks->filter(fn($t) => $t->due_date && Carbon::parse($t->due_date)->isToday())->values();
        $inProgressTasks = $myTasks->where('status', 'in_progress')->values();
        $todoTasks       = $myTasks->where('status', 'todo')->values();

        // ── 내 액션 아이템 ─────────────────────────────────
        $myActionItems = ActionItem::where(fn($q) => $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id))
            ->where('is_completed', false)
            ->with('project:id,name')
            ->orderBy('due_date')
            ->get();

        $overdueActions  = $myActionItems->filter(fn($a) => $a->due_date && Carbon::parse($a->due_date)->lt($today))->values();
        $dueTodayActions = $myActionItems->filter(fn($a) => $a->due_date && Carbon::parse($a->due_date)->isToday())->values();

        // ── 담당 이슈 ──────────────────────────────────────
        $myIssues = Issue::where('assignee_id', $user->id)
            ->whereNotIn('status', ['해결', '종결', '반려'])
            ->with('project:id,name')
            ->orderByRaw("FIELD(priority,'critical','high','medium','low')")
            ->limit(20)
            ->get();

        // ── 회의 액션 아이템 ───────────────────────────────
        $myMeetingItems = MeetingActionItem::where('owner_id', $user->id)
            ->where('status', '!=', 'completed')
            ->with('minute:id,title')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // ── 이번 주 주간보고 ───────────────────────────────
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $thisWeekReport = WeeklyReport::where('user_id', $user->id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->with('project:id,name')
            ->first();

        // ── 요약 통계 ──────────────────────────────────────
        $stats = [
            'overdue'     => $overdueTasks->count() + $overdueActions->count(),
            'due_today'   => $dueTodayTasks->count() + $dueTodayActions->count(),
            'in_progress' => $inProgressTasks->count(),
            'total_open'  => $myTasks->count() + $myActionItems->count() + $myIssues->count(),
        ];

        return response()->json([
            'stats'              => $stats,
            'overdue_tasks'      => $overdueTasks->map(fn($t) => $this->taskResource($t)),
            'due_today_tasks'    => $dueTodayTasks->map(fn($t) => $this->taskResource($t)),
            'in_progress_tasks'  => $inProgressTasks->map(fn($t) => $this->taskResource($t)),
            'todo_tasks'         => $todoTasks->map(fn($t) => $this->taskResource($t)),
            'overdue_actions'    => $overdueActions->map(fn($a) => $this->actionResource($a)),
            'due_today_actions'  => $dueTodayActions->map(fn($a) => $this->actionResource($a)),
            'my_issues'          => $myIssues->map(fn($i) => $this->issueResource($i)),
            'meeting_items'      => $myMeetingItems->map(fn($m) => [
                'id'         => $m->id,
                'content'    => $m->content,
                'status'     => $m->status,
                'due_date'   => optional($m->due_date)->format('Y-m-d'),
                'minute'     => $m->minute ? ['id' => $m->minute->id, 'title' => $m->minute->title] : null,
            ]),
            'this_week_report'   => $thisWeekReport ? [
                'id'      => $thisWeekReport->id,
                'status'  => $thisWeekReport->status,
                'project' => $thisWeekReport->project ? ['id' => $thisWeekReport->project->id, 'name' => $thisWeekReport->project->name] : null,
            ] : null,
            'week_start'         => $weekStart->format('Y-m-d'),
        ]);
    }

    private function taskResource(Task $t): array
    {
        return [
            'id'             => $t->id,
            'title'          => $t->title,
            'status'         => $t->status,
            'priority'       => $t->priority,
            'priority_label' => $this->priorityLabel($t->priority),
            'due_date'       => optional($t->due_date)->format('Y-m-d'),
            'project'        => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
        ];
    }

    private function actionResource(ActionItem $a): array
    {
        return [
            'id'        => $a->id,
            'title'     => $a->title,
            'due_date'  => optional($a->due_date)->format('Y-m-d'),
            'project'   => $a->project ? ['id' => $a->project->id, 'name' => $a->project->name] : null,
        ];
    }

    private function issueResource(Issue $i): array
    {
        return [
            'id'             => $i->id,
            'project_id'     => $i->project_id,
            'title'          => $i->title,
            'status'         => $i->status,
            'priority'       => $i->priority,
            'project'        => $i->project ? ['id' => $i->project->id, 'name' => $i->project->name] : null,
        ];
    }

    private function priorityLabel(?string $p): string
    {
        return match ($p) {
            'high'   => '높음',
            'medium' => '보통',
            'low'    => '낮음',
            default  => '보통',
        };
    }
}