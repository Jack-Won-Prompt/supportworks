<?php

namespace App\Http\Controllers;

use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\PlanningCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PlanningApprovalController extends Controller
{
    public function __construct(
        private readonly PlanningCompletionService $completionService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::PLANNING)
            ->firstOrFail();

        $gate = AiAgentApprovalGate::where('stage_id', $stage->id)
            ->where('gate_type', 'stage_completion')
            ->latest('requested_at')
            ->first();

        $diagnosis = $this->completionService->analyze($project->id, $stage->id);

        $pageTitle  = '기획 단계 승인';
        $stageLabel = '단계 1: 기획';

        return view('ai-agent.planning.approval.index', compact('project', 'stage', 'gate', 'diagnosis', 'pageTitle', 'stageLabel'));
    }

    public function diagnosis(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::PLANNING)
            ->firstOrFail();

        $diagnosis = $this->completionService->analyze($project->id, $stage->id);

        return response()->json($diagnosis);
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
