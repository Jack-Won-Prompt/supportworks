<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Issue;
use App\Models\MeetingActionItem;
use App\Models\Project;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\View\View;

class MyWorkController extends Controller
{
    public function index(): View
    {
        $user  = auth()->user();
        $today = today();

        // ── Tasks (개인 할일) ──────────────────────────────
        $myTasks = Task::where('user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->with('project:id,name')
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->orderBy('due_date')
            ->get();

        $inProgressTasks = $myTasks->where('status', 'in_progress')->values();
        $todoTasks        = $myTasks->where('status', 'todo')->values();
        $overdueTasks     = $myTasks->filter(fn($t) => $t->due_date && $t->due_date->lt($today))->values();
        $dueTodayTasks    = $myTasks->filter(fn($t) => $t->due_date && $t->due_date->isToday())->values();

        $recentDoneTasks  = Task::where('user_id', $user->id)
            ->where('status', 'done')
            ->with('project:id,name')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // ── Action Items ───────────────────────────────────
        $myActionItems = ActionItem::where(fn($q) => $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id))
            ->where('is_completed', false)
            ->with(['project:id,name', 'assignedUser:id,name', 'creator:id,name'])
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $overdueActionItems  = $myActionItems->filter(fn($a) => $a->due_date && $a->due_date->lt($today))->values();
        $dueTodayActionItems = $myActionItems->filter(fn($a) => $a->due_date && $a->due_date->isToday())->values();

        $recentDoneActions = ActionItem::where(fn($q) => $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id))
            ->where('is_completed', true)
            ->with('project:id,name')
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();

        // ── SubTasks (프로젝트 담당 작업) ──────────────────
        $mySubTasks = SubTask::where('assignee_id', $user->id)
            ->whereIn('status', ['not_started', 'in_progress'])
            ->with(['project:id,name', 'taskGroup:id,title'])
            ->orderBy('end_date')
            ->limit(30)
            ->get();

        $overdueSubTasks = $mySubTasks->filter(fn($s) => $s->end_date && $s->end_date->lt($today))->values();

        // ── Issues (담당 이슈) ─────────────────────────────
        $myIssues = Issue::where('assignee_id', $user->id)
            ->whereNotIn('status', ['해결', '종결', '반려'])
            ->with('project:id,name')
            ->orderByRaw("FIELD(priority,'critical','high','medium','low')")
            ->limit(20)
            ->get();

        // ── 회의 Action Items ──────────────────────────────
        $myMeetingItems = MeetingActionItem::where('owner_id', $user->id)
            ->where('status', '!=', 'completed')
            ->with('minute:id,title')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // ── 이번 주 위클리 ─────────────────────────────────
        $weekStart         = $today->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $thisWeekReport    = WeeklyReport::where('user_id', $user->id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->with('project:id,name')
            ->first();

        // ── 요약 통계 ──────────────────────────────────────
        $stats = [
            'overdue'     => $overdueTasks->count() + $overdueActionItems->count() + $overdueSubTasks->count(),
            'due_today'   => $dueTodayTasks->count() + $dueTodayActionItems->count(),
            'in_progress' => $inProgressTasks->count() + $mySubTasks->where('status', 'in_progress')->count(),
            'total_open'  => $myTasks->count() + $myActionItems->count() + $mySubTasks->count(),
        ];

        // ── 빠른 등록용 ────────────────────────────────────
        $projects   = $user->isAdmin()
            ? Project::companyOf($user)->orderBy('name')->get(['id', 'name'])
            : $user->projects()->orderBy('name')->get(['projects.id', 'projects.name']);

        $teammates  = User::companyOf($user)->where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name']);

        return view('my-work.index', compact(
            'user',
            'inProgressTasks', 'todoTasks', 'overdueTasks', 'dueTodayTasks', 'recentDoneTasks',
            'myActionItems', 'overdueActionItems', 'dueTodayActionItems', 'recentDoneActions',
            'mySubTasks', 'overdueSubTasks',
            'myIssues',
            'myMeetingItems',
            'thisWeekReport', 'weekStart',
            'stats',
            'projects', 'teammates',
        ));
    }
}
