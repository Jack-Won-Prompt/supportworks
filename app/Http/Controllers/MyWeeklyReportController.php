<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WeeklyAiSummary;
use App\Models\WeeklyReport;
use Illuminate\View\View;

class MyWeeklyReportController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // 매니저 권한 = 사이트 어드민 또는 어느 프로젝트에서든 manager 역할
        $isManager = $user->isAdmin()
            || $user->projectMembers()->where('role', 'manager')->exists();

        $query = WeeklyReport::with(['project:id,name', 'user:id,name,avatar'])
            ->withCount(['tasks as current_task_count' => fn($q) => $q->where('section', 'current_week')])
            ->withCount(['tasks as next_task_count'    => fn($q) => $q->where('section', 'next_week')])
            ->orderByDesc('week_start_date')
            ->orderByDesc('updated_at');

        if (!$isManager) {
            // 팀원: 본인 보고서만
            $query->where('user_id', $user->id);
        } elseif (!$user->isAdmin()) {
            // 매니저(비관리자): 자기 회사 사용자들의 보고서만
            $companyGroupId = $user->company_group_id;
            if ($companyGroupId) {
                $query->whereHas('user', function ($q) use ($companyGroupId) {
                    $q->where('company_group_id', $companyGroupId);
                });
            } else {
                // 회사 미소속 매니저는 본인 것만
                $query->where('user_id', $user->id);
            }
        }
        // 관리자: 제한 없음 (전체 조회)

        $reports = $query->get();

        // 매니저 전용 데이터
        $managerProjects = collect();
        $projectWeeksMap = [];  // project_id => [week_start_date list]

        if ($isManager) {
            if ($user->isAdmin()) {
                $managerProjects = Project::orderBy('name')->get(['id', 'name']);
            } else {
                $managerProjects = Project::whereHas('projectMembers', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('role', 'manager');
                })->orderBy('name')->get(['id', 'name']);
            }

            // 각 프로젝트의 주차 목록 (웍스 서머리 주차 선택용)
            foreach ($managerProjects as $mp) {
                $weeks = WeeklyReport::where('project_id', $mp->id)
                    ->selectRaw('week_start_date, year, week_number')
                    ->distinct()
                    ->orderByDesc('week_start_date')
                    ->get();
                $projectWeeksMap[$mp->id] = $weeks->map(fn($w) => [
                    'date'  => $w->week_start_date instanceof \Carbon\Carbon
                                ? $w->week_start_date->format('Y-m-d')
                                : \Carbon\Carbon::parse($w->week_start_date)->format('Y-m-d'),
                    'label' => \Carbon\Carbon::parse($w->week_start_date)->locale('ko')->isoFormat('YYYY년 M월 W주차'),
                ])->values()->all();
            }
        }

        // 보고서 작성 가능한 프로젝트 (본인이 멤버인 모든 프로젝트)
        $userProjects = Project::whereHas('projectMembers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orderBy('name')->get(['id', 'name']);

        return view('my-weekly.index', compact(
            'reports', 'user', 'isManager', 'managerProjects', 'projectWeeksMap', 'userProjects'
        ));
    }
}
