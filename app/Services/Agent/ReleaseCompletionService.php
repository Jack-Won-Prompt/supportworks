<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentUsageLog;
use App\Models\Project;

class ReleaseCompletionService
{
    private const ARTIFACTS = [
        ArtifactType::RELEASE_PACKAGE => [
            'level'       => 'blocking',
            'label'       => '통합 릴리즈 패키지',
            'source_task' => 'T48',
            'route_name'  => 'ai-agent.projects.release.package.index',
        ],
        ArtifactType::DEPLOY_GUIDE => [
            'level'       => 'blocking',
            'label'       => '배포 가이드',
            'source_task' => 'T49',
            'route_name'  => 'ai-agent.projects.release.deploy-guide.index',
        ],
        ArtifactType::USER_MANUAL => [
            'level'       => 'warning',
            'label'       => '사용자 매뉴얼',
            'source_task' => 'T50',
            'route_name'  => 'ai-agent.projects.release.user-manual.index',
        ],
        ArtifactType::MIGRATION_GUIDE => [
            'level'       => 'warning',
            'label'       => '마이그레이션 가이드',
            'source_task' => 'T51',
            'route_name'  => 'ai-agent.projects.release.migration-guide.index',
        ],
    ];

    public function analyze(int $projectId): array
    {
        $blocking = [];
        $warnings = [];

        foreach (self::ARTIFACTS as $type => $policy) {
            $artifact = AiAgentArtifact::where('project_id', $projectId)
                ->where('type', $type->value)
                ->where('scope_type', 'project')
                ->latest('created_at')
                ->first();

            $result = [
                'type'        => $type->value,
                'label'       => $policy['label'],
                'source_task' => $policy['source_task'],
                'route_name'  => $policy['route_name'],
                'complete'    => $artifact !== null,
                'generated_at'=> $artifact?->created_at?->format('Y.m.d H:i'),
                'note'        => $artifact
                    ? "생성됨 ({$artifact->created_at->format('Y.m.d')})"
                    : "{$policy['label']}이(가) 없습니다. {$policy['source_task']}을(를) 먼저 실행하세요",
            ];

            if ($policy['level'] === 'blocking') {
                $blocking[] = $result;
            } else {
                $warnings[] = $result;
            }
        }

        $blockingComplete = count(array_filter($blocking, fn($i) => $i['complete']));
        $warningComplete  = count(array_filter($warnings, fn($i) => $i['complete']));
        $totalItems       = count($blocking) + count($warnings);
        $doneItems        = $blockingComplete + $warningComplete;
        $overallPct       = $totalItems > 0 ? (int) round($doneItems / $totalItems * 100) : 0;

        $missingRequired = array_values(
            array_map(fn($i) => $i['label'], array_filter($blocking, fn($i) => !$i['complete']))
        );

        return [
            'blocking'          => $blocking,
            'warnings'          => $warnings,
            'blocking_total'    => count($blocking),
            'blocking_complete' => $blockingComplete,
            'warning_total'     => count($warnings),
            'warning_complete'  => $warningComplete,
            'overall_percent'   => $overallPct,
            'can_request'       => count($missingRequired) === 0,
            'missing_required'  => $missingRequired,
        ];
    }

    public function collectProjectStats(int $projectId): array
    {
        $project   = Project::findOrFail($projectId);
        $usageLogs = AiAgentUsageLog::where('project_id', $projectId)->get();

        $approvals = AiAgentApprovalGate::where('project_id', $projectId)
            ->where('status', 'approved')
            ->where('gate_type', 'stage_completion')
            ->with(['reviewedBy', 'stage'])
            ->orderBy('reviewed_at')
            ->get();

        return [
            'project_name'       => $project->name,
            'total_phases'       => 5,
            'completed_phases'   => $approvals->count(),
            'total_artifacts'    => AiAgentArtifact::where('project_id', $projectId)->count(),
            'total_screens'      => AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->count(),
            'total_requirements' => AiAgentRequirement::where('project_id', $projectId)->count(),
            'total_ai_calls'     => $usageLogs->count(),
            'total_tokens'       => $usageLogs->sum('input_tokens') + $usageLogs->sum('output_tokens'),
            'total_cost_usd'     => round((float) $usageLogs->sum('cost_usd'), 2),
            'started_at'         => $project->created_at,
            'duration_days'      => $project->created_at->diffInDays(now()),
            'approvals'          => $approvals->map(fn($g) => [
                'stage'       => $g->stage?->type?->value,
                'stage_label' => $g->stage?->type?->label() ?? $g->stage?->name ?? '알 수 없음',
                'approved_at' => $g->reviewed_at?->format('Y.m.d'),
                'approver'    => $g->reviewedBy?->name ?? '알 수 없음',
            ])->all(),
        ];
    }

    public function canRequestApproval(int $projectId): bool
    {
        $report = $this->analyze($projectId);
        return $report['can_request'];
    }
}
