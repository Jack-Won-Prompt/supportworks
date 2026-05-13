<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\AiSession;
use App\Models\CommunityPost;
use App\Models\MeetingMinute;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Question;
use App\Models\Schedule;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        if ($user->isAdmin()) {
            $projectIds       = Project::pluck('id');
            $projects         = Project::with('creator')->latest()->take(5)->get();
            $totalProjects    = Project::count();
            $activeProjects   = Project::where('status', 'active')->count();
            $pendingQuestions = Question::where('status', 'open')->count();
            $calendarSchedules = Schedule::whereBetween('start_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'completed')
                ->orderBy('start_date')
                ->with(['project'])->get();
        } else {
            $projectIds       = $user->projects()->pluck('projects.id');
            $projects         = Project::whereIn('id', $projectIds)->with('creator')->latest()->take(5)->get();
            $totalProjects    = $projectIds->count();
            $activeProjects   = Project::whereIn('id', $projectIds)->where('status', 'active')->count();
            $pendingQuestions = Question::whereIn('project_id', $projectIds)->where('status', 'open')->count();
            $calendarSchedules = Schedule::whereIn('project_id', $projectIds)
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'completed')
                ->orderBy('start_date')
                ->with(['project'])->get();
        }

        $pendingActions = ActionItem::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            })
            ->where('is_completed', false)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderByDesc('created_at')
            ->with(['project'])
            ->get();

        $calendarActionItems = ActionItem::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            })
            ->where('is_completed', false)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->with(['project'])
            ->orderBy('due_date')
            ->get();

        $myTasks = Task::where('user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->orderByRaw("FIELD(status,'in_progress','todo')")
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->take(5)->with('project')->get();

        $recentMinutes = MeetingMinute::companyOf($user)
            ->with(['author', 'project', 'actionItems'])
            ->latest('meeting_date')->take(4)->get();

        $minutesThisMonth = MeetingMinute::companyOf($user)
            ->whereMonth('meeting_date', now()->month)
            ->whereYear('meeting_date', now()->year)
            ->count();

        $recentAiSessions = AiSession::where('user_id', $user->id)
            ->withCount('messages')
            ->latest()
            ->take(4)->get();

        $totalAiSessions = AiSession::where('user_id', $user->id)->count();

        $pendingActionItems = $pendingActions->count();
        $todoTasks = $myTasks->count();

        $recentCommunityPosts = CommunityPost::companyOf($user)
            ->withCount('allComments')
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        $recentFiles = ProjectFile::whereIn('project_id', $projectIds)
            ->with(['project', 'uploader'])
            ->withCount('comments')
            ->latest()
            ->take(12)
            ->get()
            ->filter(fn($f) => $f->previewType() !== null)
            ->take(6)
            ->values();

        return view('dashboard', compact(
            'projects', 'totalProjects', 'activeProjects',
            'pendingQuestions', 'calendarSchedules', 'calendarActionItems',
            'pendingActions', 'myTasks',
            'recentMinutes', 'minutesThisMonth',
            'recentAiSessions', 'totalAiSessions',
            'pendingActionItems', 'todoTasks',
            'recentCommunityPosts', 'recentFiles'
        ));
    }
}
