<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ActionItemController extends Controller
{
    public function index(Request $request)
    {
        $user      = auth()->user();
        $filter    = $request->query('filter', 'all');
        $projectId = $request->query('project_id');

        $query = ActionItem::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('assigned_to', $user->id);
        })->with(['creator', 'assignedUser', 'project']);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($filter === 'pending') {
            $query->where('is_completed', false);
        } elseif ($filter === 'done') {
            $query->where('is_completed', true);
        } elseif ($filter === 'mine') {
            $query->where('assigned_to', $user->id)->where('is_completed', false);
        }

        $items = $query->orderBy('is_completed')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $projects  = $user->isAdmin()
            ? Project::companyOf($user)->orderBy('name')->get()
            : $user->projects()->orderBy('name')->get();

        $selectedProject = $projectId ? $projects->firstWhere('id', (int) $projectId) : null;

        $teammates = User::companyOf($user)->where('id', '!=', $user->id)->orderBy('name')->get();

        // 전체 통계 (필터 무관, 프로젝트 필터는 반영)
        $allItems = ActionItem::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
        })->when($projectId, fn($q) => $q->where('project_id', $projectId))
          ->with(['assignedUser', 'creator'])->get();

        $stats = [
            'total'     => $allItems->count(),
            'pending'   => $allItems->where('is_completed', false)->count(),
            'completed' => $allItems->where('is_completed', true)->count(),
            'overdue'   => $allItems->where('is_completed', false)
                               ->filter(fn($i) => $i->due_date && $i->due_date->lt(today()))->count(),
        ];

        // 담당자별 미완료 현황
        $assigneeStats = $allItems->where('is_completed', false)
            ->groupBy(fn($i) => $i->assigned_to ?? $i->user_id)
            ->map(fn($group) => [
                'name'  => $group->first()->assignedUser?->name ?? $group->first()->creator?->name ?? '본인',
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        // 이번 주 마감 (미완료)
        $dueThisWeek = $allItems->where('is_completed', false)
            ->filter(fn($i) => $i->due_date && $i->due_date->between(today(), today()->addDays(6)))
            ->sortBy('due_date')
            ->take(5);

        // 최근 완료
        $recentlyDone = $allItems->where('is_completed', true)
            ->sortByDesc('completed_at')
            ->take(5);

        return view('action-items.index', compact(
            'items', 'filter', 'projects', 'teammates',
            'stats', 'assigneeStats', 'dueThisWeek', 'recentlyDone',
            'projectId', 'selectedProject'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'due_date'    => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'project_id'  => 'nullable|exists:projects,id',
        ]);

        $user = auth()->user();

        if ($request->assigned_to) {
            $assignee = User::find($request->assigned_to);
            if ($user->hasCompany() && $assignee && !$user->inSameCompany($assignee)) {
                return back()->with('error', '같은 회사 구성원에게만 할당할 수 있습니다.');
            }
        }

        ActionItem::create([
            'user_id'     => $user->id,
            'assigned_to' => $request->assigned_to,
            'project_id'  => $request->project_id,
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
        ]);

        return back()->with('success', 'Action 아이템이 추가되었습니다.');
    }

    public function toggle(ActionItem $actionItem)
    {
        $user = auth()->user();
        abort_if(
            $actionItem->user_id !== $user->id && $actionItem->assigned_to !== $user->id,
            403
        );

        $completed = !$actionItem->is_completed;
        $actionItem->update([
            'is_completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ]);

        return back();
    }

    public function destroy(ActionItem $actionItem)
    {
        abort_if($actionItem->user_id !== auth()->id(), 403);
        $actionItem->delete();
        return back()->with('success', '삭제되었습니다.');
    }
}
