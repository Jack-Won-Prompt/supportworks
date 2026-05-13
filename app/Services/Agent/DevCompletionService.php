<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;

class DevCompletionService
{
    private const BACKEND_COVERAGE_THRESHOLD  = 0.80;
    private const API_COMPLIANCE_THRESHOLD    = 80;
    private const CODE_REVIEW_SCORE_THRESHOLD = 80;
    private const ISSUE_RESOLUTION_THRESHOLD  = 70;

    public function __construct(
        private readonly IssueAggregator $issueAggregator,
    ) {}

    public function analyze(int $projectId, int $stageId): array
    {
        $blocking = $this->checkBlocking($projectId);
        $warnings = $this->checkWarnings($projectId);

        $blockingComplete = count(array_filter($blocking, fn($i) => $i['complete']));
        $warningComplete  = count(array_filter($warnings, fn($i) => $i['complete']));
        $totalItems       = count($blocking) + count($warnings);
        $doneItems        = $blockingComplete + $warningComplete;
        $overallPct       = $totalItems > 0 ? (int) round($doneItems / $totalItems * 100) : 0;

        $missingRequired = array_values(
            array_map(fn($i) => $i['label'], array_filter($blocking, fn($i) => !$i['complete']))
        );

        // Extra stats for the UI
        $screens        = AiAgentScreen::where('project_id', $projectId)->active()->get();
        $totalScreens   = $screens->count();
        $totalResources = $this->countErdResources($projectId);

        return [
            'blocking'            => $blocking,
            'warnings'            => $warnings,
            'blocking_total'      => count($blocking),
            'blocking_complete'   => $blockingComplete,
            'warning_total'       => count($warnings),
            'warning_complete'    => $warningComplete,
            'overall_percent'     => $overallPct,
            'can_request'         => count($missingRequired) === 0,
            'missing_required'    => $missingRequired,
            'total_screens'       => $totalScreens,
            'total_resources'     => $totalResources,
        ];
    }

    // ── Blocking checks ───────────────────────────────────────────────────────

    private function checkBlocking(int $projectId): array
    {
        return [
            $this->checkBackendCode($projectId),
            $this->checkApiIntegration($projectId),
            $this->checkCodeReview($projectId),
        ];
    }

    private function checkBackendCode(int $projectId): array
    {
        $totalResources = $this->countErdResources($projectId);

        $generatedCount = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->count();

        $coverage  = $totalResources > 0 ? ($generatedCount / $totalResources) * 100 : 0;
        $threshold = self::BACKEND_COVERAGE_THRESHOLD * 100;
        $complete  = $totalResources === 0 || $coverage >= $threshold;

        $noteparts = [];
        if ($totalResources === 0) {
            $noteparts[] = 'ERD 산출물이 없습니다. T36 ERD를 먼저 생성하세요';
        } elseif (!$complete) {
            $noteparts[] = "생성됨: {$generatedCount}개 / 전체: {$totalResources}개 (목표: {$threshold}% 이상)";
        }

        return [
            'type'          => ArtifactType::BACKEND_CODE->value,
            'label'         => ArtifactType::BACKEND_CODE->label(),
            'complete'      => $complete,
            'coverage'      => $totalResources > 0 ? (int) round($coverage) : null,
            'covered'       => $generatedCount,
            'total'         => $totalResources,
            'note'          => implode(' · ', $noteparts) ?: "리소스 {$threshold}% 이상 생성 필요",
            'source_task'   => 'T43',
        ];
    }

    private function checkApiIntegration(int $projectId): array
    {
        $artifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_INTEGRATION->value)
            ->latest('created_at')
            ->first();

        if (!$artifact) {
            return [
                'type'        => ArtifactType::API_INTEGRATION->value,
                'label'       => ArtifactType::API_INTEGRATION->label(),
                'complete'    => false,
                'coverage'    => null,
                'covered'     => null,
                'total'       => null,
                'note'        => 'API 연계 분석이 없습니다. T44 API 연계를 먼저 실행하세요',
                'source_task' => 'T44',
            ];
        }

        $content  = json_decode($artifact->content, true) ?? [];
        $rate     = $content['analysis']['$metadata']['compliance_rate'] ?? 0;
        $matched  = $content['analysis']['$metadata']['matched'] ?? 0;
        $total    = $content['analysis']['$metadata']['frontend_calls'] ?? 0;
        $complete = $rate >= self::API_COMPLIANCE_THRESHOLD;

        return [
            'type'             => ArtifactType::API_INTEGRATION->value,
            'label'            => ArtifactType::API_INTEGRATION->label(),
            'complete'         => $complete,
            'compliance_rate'  => $rate,
            'covered'          => $matched,
            'total'            => $total,
            'coverage'         => (int) round($rate),
            'note'             => $complete
                ? "매칭률 {$rate}% — 충족"
                : "매칭률 {$rate}% (목표: " . self::API_COMPLIANCE_THRESHOLD . "% 이상)",
            'source_task'      => 'T44',
        ];
    }

    private function checkCodeReview(int $projectId): array
    {
        // System-level review artifact for overall score
        $systemArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'project')
            ->latest('created_at')
            ->first();

        if (!$systemArtifact) {
            return [
                'type'           => ArtifactType::CODE_REVIEW->value,
                'label'          => ArtifactType::CODE_REVIEW->label(),
                'complete'       => false,
                'overall_score'  => null,
                'critical_count' => null,
                'note'           => '웍스 코드 리뷰가 없습니다. T45 웍스 코드 리뷰를 먼저 실행하세요',
                'source_task'    => 'T45',
            ];
        }

        $sysContent   = json_decode($systemArtifact->content, true) ?? [];
        $overallScore = $sysContent['overall_score'] ?? 0;

        // Count critical violations across screen reviews (not fixed, not ignored)
        $screenArtifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'screen')
            ->get();

        $criticalCount = 0;
        foreach ($screenArtifacts as $sa) {
            $decoded = json_decode($sa->content, true) ?? [];
            foreach ($decoded['additional_findings'] ?? [] as $f) {
                if (($f['severity'] ?? '') === 'critical'
                    && empty($f['fixed'])
                    && empty($f['ignored'])) {
                    $criticalCount++;
                }
            }
        }

        $scoreOk    = $overallScore >= self::CODE_REVIEW_SCORE_THRESHOLD;
        $criticalOk = $criticalCount === 0;
        $complete   = $scoreOk && $criticalOk;

        $noteparts = [];
        if (!$scoreOk) {
            $noteparts[] = "종합 점수 {$overallScore}점 (목표: " . self::CODE_REVIEW_SCORE_THRESHOLD . "점 이상)";
        }
        if (!$criticalOk) {
            $noteparts[] = "Critical 위반 {$criticalCount}건 해결 필요";
        }

        return [
            'type'           => ArtifactType::CODE_REVIEW->value,
            'label'          => ArtifactType::CODE_REVIEW->label(),
            'complete'       => $complete,
            'overall_score'  => $overallScore,
            'critical_count' => $criticalCount,
            'note'           => implode(' · ', $noteparts)
                ?: "종합 점수 {$overallScore}점, Critical 위반 없음",
            'source_task'    => 'T45',
        ];
    }

    // ── Warning checks ────────────────────────────────────────────────────────

    private function checkWarnings(int $projectId): array
    {
        return [
            $this->checkIssueResolution($projectId),
        ];
    }

    private function checkIssueResolution(int $projectId): array
    {
        $groups = $this->issueAggregator->aggregateIssues($projectId);

        $totalGroups    = count($groups);
        $resolvedGroups = count(array_filter($groups, fn($g) => in_array($g['status'], ['fixed', 'ignored'])));
        $pendingGroups  = $totalGroups - $resolvedGroups;
        $rate           = $totalGroups > 0 ? round($resolvedGroups / $totalGroups * 100, 1) : 100.0;
        $complete       = $rate >= self::ISSUE_RESOLUTION_THRESHOLD;

        // Break down by severity for detail
        $criticalPending = count(array_filter($groups, fn($g) => $g['severity'] === 'critical' && $g['status'] === 'pending'));
        $warningPending  = count(array_filter($groups, fn($g) => $g['severity'] === 'warning'  && $g['status'] === 'pending'));

        $noteparts = [];
        if ($totalGroups === 0) {
            $noteparts[] = '집계된 이슈가 없습니다';
        } elseif (!$complete) {
            $noteparts[] = "해결률 {$rate}% ({$resolvedGroups}/{$totalGroups}건, 목표: " . self::ISSUE_RESOLUTION_THRESHOLD . "% 이상)";
            if ($criticalPending > 0) $noteparts[] = "Critical 미해결 {$criticalPending}건";
        }

        return [
            'type'             => 'issue_resolution',
            'label'            => '이슈 해결률 (T46)',
            'complete'         => $complete,
            'coverage'         => (int) round($rate),
            'covered'          => $resolvedGroups,
            'total'            => $totalGroups,
            'pending'          => $pendingGroups,
            'critical_pending' => $criticalPending,
            'warning_pending'  => $warningPending,
            'note'             => implode(' · ', $noteparts)
                ?: "해결률 {$rate}% ({$resolvedGroups}/{$totalGroups}건)",
            'source_task'      => 'T46',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function countErdResources(int $projectId): int
    {
        $erd = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)
            ->latest('created_at')
            ->first();

        if (!$erd) return 0;

        $content = json_decode($erd->content, true) ?? [];
        return count($content['tables'] ?? []);
    }
}
