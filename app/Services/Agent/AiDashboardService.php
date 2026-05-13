<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactVersion;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentUsageLog;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Models\User;
use App\Enums\Agent\StageStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AiDashboardService
{
    /**
     * 사용자의 프로젝트를 웍스 Agent 활성/미활성으로 분리.
     *
     * @return array{enabled: Collection<int, array{project: Project, config: ProjectAiAgentConfig}>, disabled: Collection<int, Project>}
     */
    public function splitProjects(User $user): array
    {
        $allProjects = $user->isAdmin()
            ? Project::orderBy('name')->get()
            : $user->projects()->orderBy('name')->get();

        $configMap = ProjectAiAgentConfig::whereIn('project_id', $allProjects->pluck('id'))
            ->get()
            ->keyBy('project_id');

        $enabled = $allProjects
            ->filter(fn($p) => $configMap->has($p->id))
            ->map(fn($p) => ['project' => $p, 'config' => $configMap[$p->id]])
            ->values();

        $disabled = $allProjects
            ->filter(fn($p) => !$configMap->has($p->id))
            ->values();

        return compact('enabled', 'disabled');
    }

    /**
     * 프로젝트 단계 목록 (진행률 포함).
     *
     * @return Collection<int, array{stage: AiAgentProjectStage, progress: int, route_prefix: string}>
     */
    public function getStages(int $projectId): Collection
    {
        return AiAgentProjectStage::where('project_id', $projectId)
            ->orderBy('order')
            ->get()
            ->map(fn($stage) => [
                'stage'        => $stage,
                'progress'     => $this->calcProgress($stage),
                'route_prefix' => $this->stageRoutePrefix($stage->type->value),
                'icon'         => $this->stageIcon($stage->type->value),
            ]);
    }

    /**
     * 프로젝트 전체 진행률 (0-100, 5단계 평균).
     */
    public function getOverallProgress(int $projectId): int
    {
        $stages = AiAgentProjectStage::where('project_id', $projectId)->get();
        if ($stages->isEmpty()) return 0;

        return (int) round(
            $stages->sum(fn($s) => $this->calcProgress($s)) / $stages->count()
        );
    }

    /**
     * 웍스 사용량 통계 (5분 캐싱).
     *
     * @return array{input_tokens: int, output_tokens: int, cost_usd: float, call_count: int}
     */
    public function getUsageStats(int $projectId): array
    {
        return Cache::remember("ai-agent:usage:{$projectId}", 300, function () use ($projectId) {
            $row = AiAgentUsageLog::forProject($projectId)
                ->successful()
                ->selectRaw('SUM(input_tokens) as input, SUM(output_tokens) as output, SUM(cost_usd) as cost, COUNT(*) as calls')
                ->first();

            return [
                'input_tokens'  => (int) ($row->input  ?? 0),
                'output_tokens' => (int) ($row->output ?? 0),
                'cost_usd'      => (float) ($row->cost ?? 0),
                'call_count'    => (int) ($row->calls  ?? 0),
            ];
        });
    }

    /**
     * 승인 대기 현황.
     *
     * @return array{total_pending: int, requested_by_me: int}
     */
    public function getPendingApprovals(int $projectId, int $userId): array
    {
        $total = AiAgentApprovalGate::where('project_id', $projectId)
            ->pending()
            ->count();

        $mine = AiAgentApprovalGate::where('project_id', $projectId)
            ->pending()
            ->where('requested_by', $userId)
            ->count();

        return ['total_pending' => $total, 'requested_by_me' => $mine];
    }

    /**
     * 활동 타임라인 (산출물 버전 + 승인 이벤트 + 웍스 호출, 최신순).
     *
     * @return array<int, array{type: string, title: string, desc: string, user: string, created_at: \Carbon\Carbon, icon: string}>
     */
    public function getActivityTimeline(int $projectId, int $limit = 30): array
    {
        $stageIds    = AiAgentProjectStage::where('project_id', $projectId)->pluck('id');
        $artifactIds = AiAgentArtifact::whereIn('stage_id', $stageIds)->pluck('id');

        // 1. 산출물 버전 이력
        $versionEvents = AiAgentArtifactVersion::with(['artifact', 'creator'])
            ->whereIn('artifact_id', $artifactIds)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($v) => [
                'type'       => 'artifact_version',
                'title'      => ($v->artifact?->title ?? '산출물') . ' ' . ($v->version === 1 ? '생성' : 'v' . $v->version . ' 업데이트'),
                'desc'       => $v->change_summary ?: '내용이 수정되었습니다.',
                'user'       => $v->creator?->name ?? '시스템',
                'created_at' => $v->created_at,
                'icon'       => 'document',
            ]);

        // 2. 승인 게이트 이벤트
        $approvalEvents = AiAgentApprovalGate::with(['requestedBy', 'reviewedBy', 'stage'])
            ->where('project_id', $projectId)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->flatMap(function ($gate) {
                $items = [];
                if ($gate->requested_at) {
                    $items[] = [
                        'type'       => 'approval_request',
                        'title'      => ($gate->stage?->name ?? '단계') . ' 승인 요청',
                        'desc'       => $gate->request_comment ?: '승인을 요청했습니다.',
                        'user'       => $gate->requestedBy?->name ?? '알 수 없음',
                        'created_at' => $gate->requested_at,
                        'icon'       => 'clock',
                    ];
                }
                if ($gate->reviewed_at) {
                    $isApproved = $gate->status->value === 'approved';
                    $items[] = [
                        'type'       => $isApproved ? 'approval_done' : 'approval_rejected',
                        'title'      => ($gate->stage?->name ?? '단계') . ' 승인 ' . ($isApproved ? '완료' : '반려'),
                        'desc'       => $gate->review_comment ?: ($isApproved ? '승인되었습니다.' : '반려되었습니다.'),
                        'user'       => $gate->reviewedBy?->name ?? '알 수 없음',
                        'created_at' => $gate->reviewed_at,
                        'icon'       => $isApproved ? 'check' : 'x',
                    ];
                }
                return $items;
            });

        // 3. 웍스 호출 완료
        $aiEvents = AiAgentUsageLog::forProject($projectId)
            ->successful()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'type'       => 'ai_call',
                'title'      => '웍스 분석 완료',
                'desc'       => ($log->task_type ?? '작업') . ' — ' . number_format($log->input_tokens + $log->output_tokens) . ' 토큰',
                'user'       => $log->user?->name ?? '시스템',
                'created_at' => $log->created_at,
                'icon'       => 'sparkle',
            ]);

        return collect($versionEvents)
            ->concat($approvalEvents)
            ->concat($aiEvents)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * 사용자의 마지막 활성 웍스 Agent 프로젝트 ID.
     */
    public function getLastActiveProjectId(User $user): ?int
    {
        $projectIds = $user->isAdmin()
            ? Project::pluck('id')->all()
            : $user->projects()->pluck('projects.id')->all();

        return ProjectAiAgentConfig::whereIn('project_id', $projectIds)
            ->orderByDesc('updated_at')
            ->value('project_id');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function calcProgress(AiAgentProjectStage $stage): int
    {
        return match ($stage->status) {
            StageStatus::LOCKED           => 0,
            StageStatus::IN_PROGRESS      => $this->calcInProgressPercent($stage),
            StageStatus::PENDING_APPROVAL => 80,
            StageStatus::APPROVED         => 100,
        };
    }

    private function calcInProgressPercent(AiAgentProjectStage $stage): int
    {
        $total = $stage->artifacts()->count();
        if ($total === 0) return 10;

        $done = $stage->artifacts()->where('status', 'approved')->count();
        return max(10, min(75, (int) round($done / $total * 100)));
    }

    private function stageRoutePrefix(string $type): string
    {
        return match ($type) {
            'planning'    => 'planning',
            'design'      => 'design',
            'dev_prep'    => 'pre-dev',
            'development' => 'dev',
            'release'     => 'release',
            default       => 'planning',
        };
    }

    private function stageIcon(string $type): string
    {
        return match ($type) {
            'planning'    => '🎯',
            'design'      => '🎨',
            'dev_prep'    => '⚙️',
            'development' => '💻',
            'release'     => '📦',
            default       => '📋',
        };
    }
}
