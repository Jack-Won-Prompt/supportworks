<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ActionItem;
use App\Models\CommunityPost;
use App\Models\MeetingMinute;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        if ($user->isAdmin()) {
            $projectIds     = Project::pluck('id');
            $projects       = Project::with('creator')->latest()->take(5)->get();
            $totalProjects  = Project::count();
            $activeProjects = Project::where('status', 'active')->count();
        } else {
            $projectIds     = $user->projects()->pluck('projects.id');
            $projects       = Project::whereIn('id', $projectIds)->with('creator')->latest()->take(5)->get();
            $totalProjects  = $projectIds->count();
            $activeProjects = Project::whereIn('id', $projectIds)->where('status', 'active')->count();
        }

        $myTasks = Task::where('user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->orderByRaw("FIELD(status,'in_progress','todo')")
            ->orderByRaw("FIELD(priority,'high','medium','low')")
            ->take(5)->with('project')->get();

        $pendingActions = ActionItem::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            })
            ->where('is_completed', false)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->take(5)->with('project')->get();

        $upcomingSchedules = Schedule::whereIn('project_id', $projectIds)
            ->whereBetween('start_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->where('status', '!=', 'completed')
            ->orderBy('start_date')
            ->with('project')
            ->take(5)
            ->get();

        $recentMinutes = MeetingMinute::companyOf($user)
            ->with(['author', 'project'])
            ->latest('meeting_date')
            ->take(3)
            ->get();

        $recentPosts = CommunityPost::companyOf($user)
            ->withCount('allComments')
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_projects'   => $totalProjects,
                'active_projects'  => $activeProjects,
                'my_tasks'         => $myTasks->count(),
                'pending_actions'  => $pendingActions->count(),
            ],
            'projects'           => $this->projectsResource($projects),
            'my_tasks'           => $this->tasksResource($myTasks),
            'pending_actions'    => $this->actionItemsResource($pendingActions),
            'upcoming_schedules' => $this->schedulesResource($upcomingSchedules),
            'recent_minutes'     => $this->minutesResource($recentMinutes),
            'recent_posts'       => $this->postsResource($recentPosts),
        ]);
    }

    private function projectsResource($projects): array
    {
        return $projects->map(fn($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'status'      => $p->status,
            'description' => $p->description,
            'start_date'  => $p->start_date,
            'end_date'    => $p->end_date,
            'creator'     => $p->creator ? ['id' => $p->creator->id, 'name' => $p->creator->name] : null,
        ])->toArray();
    }

    private function tasksResource($tasks): array
    {
        return $tasks->map(fn($t) => [
            'id'          => $t->id,
            'title'       => $t->title,
            'status'      => $t->status,
            'priority'    => $t->priority,
            'due_date'    => $t->due_date,
            'project'     => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
        ])->toArray();
    }

    private function actionItemsResource($items): array
    {
        return $items->map(fn($a) => [
            'id'           => $a->id,
            'title'        => $a->title,
            'is_completed' => $a->is_completed,
            'due_date'     => $a->due_date,
            'project'      => $a->project ? ['id' => $a->project->id, 'name' => $a->project->name] : null,
        ])->toArray();
    }

    private function schedulesResource($schedules): array
    {
        return $schedules->map(fn($s) => [
            'id'         => $s->id,
            'title'      => $s->title,
            'status'     => $s->status,
            'start_date' => $s->start_date,
            'end_date'   => $s->end_date,
            'project'    => $s->project ? ['id' => $s->project->id, 'name' => $s->project->name] : null,
        ])->toArray();
    }

    private function minutesResource($minutes): array
    {
        return $minutes->map(fn($m) => [
            'id'           => $m->id,
            'title'        => $m->title,
            'meeting_date' => $m->meeting_date,
            'author'       => $m->author ? ['id' => $m->author->id, 'name' => $m->author->name] : null,
            'project'      => $m->project ? ['id' => $m->project->id, 'name' => $m->project->name] : null,
        ])->toArray();
    }

    private function postsResource($posts): array
    {
        return $posts->map(fn($p) => [
            'id'             => $p->id,
            'title'          => $p->title,
            'all_comments_count' => $p->all_comments_count,
            'created_at'     => $p->created_at,
            'user'           => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
        ])->toArray();
    }
}