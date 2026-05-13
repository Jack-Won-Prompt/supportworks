<?php

namespace App\Http\Controllers;

use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\AiDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiDashboardController extends Controller
{
    public function __construct(private AiDashboardService $dashboard) {}

    /**
     * 모드 A: 프로젝트 선택 화면.
     * 마지막 활성 프로젝트가 있으면 자동으로 모드 B로 리다이렉트.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$request->boolean('force_home')) {
            $lastProjectId = $this->dashboard->getLastActiveProjectId($user);
            if ($lastProjectId) {
                $project = Project::find($lastProjectId);
                if ($project) {
                    return redirect()->route('ai-agent.projects.home', $project);
                }
            }
        }

        ['enabled' => $enabledProjects, 'disabled' => $disabledProjects] =
            $this->dashboard->splitProjects($user);

        // 모달에서 사용할 비활성 프로젝트 목록 (간소화)
        $selectableProjects = $disabledProjects->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->name,
        ])->values();

        // 활성 프로젝트 진행률 계산
        $enabledWithProgress = $enabledProjects->map(function ($item) {
            $progress = $this->dashboard->getOverallProgress($item['project']->id);
            return array_merge($item, ['progress' => $progress]);
        });

        return view('ai-agent.dashboard.index', [
            'enabledProjects'    => $enabledWithProgress,
            'disabledProjects'   => $disabledProjects,
            'selectableProjects' => $selectableProjects,
        ]);
    }

    /**
     * 모드 B: 프로젝트 홈 (5단계 진행 + 타임라인 + 위젯).
     */
    public function show(Project $project): View|RedirectResponse
    {
        $this->authorizeProject($project);

        $config = ProjectAiAgentConfig::forProject($project->id);

        // 웍스 Agent 미설정 프로젝트는 모드 A로
        if (!$config) {
            return redirect()->route('ai-agent.dashboard', ['force_home' => 1])
                ->with('info', '해당 프로젝트에서 웍스 Agent가 아직 설정되지 않았습니다.');
        }

        $stages   = $this->dashboard->getStages($project->id);
        $timeline = $this->dashboard->getActivityTimeline($project->id, 30);
        $usage    = $this->dashboard->getUsageStats($project->id);
        $approval = $this->dashboard->getPendingApprovals($project->id, (int) auth()->id());
        $overall  = $this->dashboard->getOverallProgress($project->id);

        // 현재 활성 단계 (가장 낮은 order 중 잠기지 않은 것)
        $activeStage = $stages->first(fn($s) => $s['stage']->status !== \App\Enums\Agent\StageStatus::LOCKED);

        return view('ai-agent.dashboard.show', compact(
            'project', 'config', 'stages', 'timeline', 'usage', 'approval', 'overall', 'activeStage'
        ));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function authorizeProject(Project $project): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        abort_unless(
            ProjectMember::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
