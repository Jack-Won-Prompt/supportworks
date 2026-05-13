<?php

namespace App\Http\Controllers;

use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\ReleaseCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReleaseApprovalController extends Controller
{
    public function __construct(
        private readonly ReleaseCompletionService $completionService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::RELEASE)
            ->firstOrFail();

        $gate = AiAgentApprovalGate::where('stage_id', $stage->id)
            ->where('gate_type', 'stage_completion')
            ->latest('requested_at')
            ->first();

        $diagnosis   = $this->completionService->analyze($project->id);
        $projectStats = $this->completionService->collectProjectStats($project->id);

        return view('ai-agent.release.approval.index', [
            'project'      => $project,
            'stage'        => $stage,
            'gate'         => $gate,
            'diagnosis'    => $diagnosis,
            'projectStats' => $projectStats,
            'pageTitle'    => '릴리즈 단계 승인',
            'stageLabel'   => '단계 5: 릴리즈',
        ]);
    }

    public function diagnosis(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $diagnosis    = $this->completionService->analyze($project->id);
        $projectStats = $this->completionService->collectProjectStats($project->id);

        return response()->json(array_merge($diagnosis, ['project_stats' => $projectStats]));
    }

    public function summary(Project $project): View
    {
        $this->authorizeProject($project);

        $projectStats = $this->completionService->collectProjectStats($project->id);
        $diagnosis    = $this->completionService->analyze($project->id);

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::RELEASE)
            ->first();

        return view('ai-agent.release.approval.summary', [
            'project'      => $project,
            'stage'        => $stage,
            'projectStats' => $projectStats,
            'diagnosis'    => $diagnosis,
        ]);
    }

    private function authorizeProject(Project $project): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->isAdmin()) return;
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
