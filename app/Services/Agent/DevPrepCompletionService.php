<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;

class DevPrepCompletionService
{
    private const SCREEN_COVERAGE_THRESHOLD = 0.80;
    private const VALIDATION_SCORE_THRESHOLD = 80;

    public function analyze(int $projectId, int $stageId): array
    {
        // Project-scope artifacts (ERD, API_SPEC, RBAC_MODEL)
        $projectArtifacts = AiAgentArtifact::where('project_id', $projectId)
            ->whereIn('type', [
                ArtifactType::ERD->value,
                ArtifactType::API_SPEC->value,
                ArtifactType::RBAC_MODEL->value,
            ])
            ->where(fn($q) => $q->whereNull('scope_type')->orWhere('scope_type', 'project'))
            ->get()
            ->keyBy(fn($a) => $a->type->value);

        $screens      = AiAgentScreen::where('project_id', $projectId)->active()->get();
        $totalScreens = $screens->count();
        $screenIds    = $screens->pluck('id')->all();

        // Screen-scope artifacts for warning checks
        $screenArtifacts = AiAgentArtifact::where('project_id', $projectId)
            ->whereIn('type', [
                ArtifactType::CODE_GEN_PROMPT->value,
                ArtifactType::FRONTEND_CODE->value,
                ArtifactType::CODE_VALIDATION->value,
            ])
            ->where('scope_type', 'screen')
            ->whereIn('scope_id', $screenIds)
            ->get();

        $blocking        = $this->checkBlocking($projectArtifacts);
        $warnings        = $this->checkWarnings($screenArtifacts, $totalScreens);
        $missingRequired = collect($blocking)->where('complete', false)->pluck('label')->values()->all();

        $blockingComplete = collect($blocking)->where('complete', true)->count();
        $warningComplete  = collect($warnings)->where('complete', true)->count();

        $totalItems = count($blocking) + count($warnings);
        $doneItems  = $blockingComplete + $warningComplete;
        $overallPct = $totalItems > 0 ? (int) round($doneItems / $totalItems * 100) : 0;

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
            'total_screens'     => $totalScreens,
        ];
    }

    private function checkBlocking($artifacts): array
    {
        $blockingTypes = [
            ArtifactType::ERD,
            ArtifactType::API_SPEC,
            ArtifactType::RBAC_MODEL,
        ];

        $result = [];
        foreach ($blockingTypes as $type) {
            $artifact = $artifacts->get($type->value);
            $complete = $artifact !== null && !empty($artifact->content);

            $result[] = [
                'type'     => $type->value,
                'label'    => $type->label(),
                'complete' => $complete,
                'artifact' => $artifact,
                'note'     => null,
            ];
        }

        return $result;
    }

    private function checkWarnings($screenArtifacts, int $totalScreens): array
    {
        $result = [];

        // Code Gen Prompt coverage
        $promptCount = $screenArtifacts->where('type', ArtifactType::CODE_GEN_PROMPT->value)->unique('scope_id')->count();
        $promptPct   = $totalScreens > 0 ? $promptCount / $totalScreens : 0;
        $result[]    = [
            'type'     => ArtifactType::CODE_GEN_PROMPT->value,
            'label'    => ArtifactType::CODE_GEN_PROMPT->label(),
            'complete' => $totalScreens === 0 || $promptPct >= self::SCREEN_COVERAGE_THRESHOLD,
            'artifact' => null,
            'coverage' => $totalScreens > 0 ? (int) round($promptPct * 100) : null,
            'covered'  => $promptCount,
            'total'    => $totalScreens,
            'note'     => '화면 80% 이상 프롬프트 생성 권장',
        ];

        // Frontend Code coverage
        $codeCount = $screenArtifacts->where('type', ArtifactType::FRONTEND_CODE->value)->unique('scope_id')->count();
        $codePct   = $totalScreens > 0 ? $codeCount / $totalScreens : 0;
        $result[]  = [
            'type'     => ArtifactType::FRONTEND_CODE->value,
            'label'    => ArtifactType::FRONTEND_CODE->label(),
            'complete' => $totalScreens === 0 || $codePct >= self::SCREEN_COVERAGE_THRESHOLD,
            'artifact' => null,
            'coverage' => $totalScreens > 0 ? (int) round($codePct * 100) : null,
            'covered'  => $codeCount,
            'total'    => $totalScreens,
            'note'     => '화면 80% 이상 코드 생성 권장',
        ];

        // Code Validation: coverage + avg score ≥80 + critical=0
        $validationArtifacts = $screenArtifacts->where('type', ArtifactType::CODE_VALIDATION->value)->unique('scope_id');
        $validationCount     = $validationArtifacts->count();
        $validationPct       = $totalScreens > 0 ? $validationCount / $totalScreens : 0;

        $avgScore     = null;
        $criticalCount = 0;
        $scoreComplete = false;

        if ($validationCount > 0) {
            $scores = $validationArtifacts->map(fn($a) => $a->meta['compliance_score'] ?? null)->filter()->values();
            if ($scores->isNotEmpty()) {
                $avgScore = (int) round($scores->average());
            }

            foreach ($validationArtifacts as $va) {
                $decoded    = is_string($va->content) ? json_decode($va->content, true) : ($va->content ?? []);
                $violations = $decoded['violations'] ?? [];
                foreach ($violations as $v) {
                    if (($v['severity'] ?? '') === 'critical' && empty($v['ignored']) && empty($v['fixed'])) {
                        $criticalCount++;
                    }
                }
            }

            $scoreComplete = $avgScore !== null
                && $avgScore >= self::VALIDATION_SCORE_THRESHOLD
                && $criticalCount === 0;
        }

        $coverageOk = $totalScreens === 0 || $validationPct >= self::SCREEN_COVERAGE_THRESHOLD;
        $complete   = $coverageOk && $scoreComplete;

        $noteparts = [];
        if (!$coverageOk) $noteparts[] = '화면 80% 이상 검증 권장';
        if ($avgScore !== null && $avgScore < self::VALIDATION_SCORE_THRESHOLD) $noteparts[] = "평균 점수 {$avgScore}점 (80점 이상 권장)";
        if ($criticalCount > 0) $noteparts[] = "Critical 위반 {$criticalCount}건 해결 필요";

        $result[] = [
            'type'          => ArtifactType::CODE_VALIDATION->value,
            'label'         => ArtifactType::CODE_VALIDATION->label(),
            'complete'      => $complete,
            'artifact'      => null,
            'coverage'      => $totalScreens > 0 ? (int) round($validationPct * 100) : null,
            'covered'       => $validationCount,
            'total'         => $totalScreens,
            'avg_score'     => $avgScore,
            'critical_count'=> $criticalCount,
            'note'          => implode(' · ', $noteparts) ?: '평균 점수 80점 이상, Critical 위반 없음 권장',
        ];

        return $result;
    }
}
