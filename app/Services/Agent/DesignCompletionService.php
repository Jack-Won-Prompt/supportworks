<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;

class DesignCompletionService
{
    private const MAPPING_THRESHOLD       = 0.70;
    private const REVIEW_SCORE_THRESHOLD  = 60;

    public function analyze(int $projectId, int $stageId): array
    {
        $artifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('stage_id', $stageId)
            ->get()
            ->keyBy(fn($a) => $a->type->value);

        $screens      = AiAgentScreen::where('project_id', $projectId)->active()->get();
        $totalScreens = $screens->count();

        $blocking        = $this->checkBlocking($artifacts);
        $warnings        = $this->checkWarnings($artifacts, $screens, $totalScreens);
        $missingRequired = collect($blocking)->where('complete', false)->pluck('label')->values()->all();

        $blockingComplete = collect($blocking)->where('complete', true)->count();
        $warningComplete  = collect($warnings)->where('complete', true)->count();

        $totalItems = count($blocking) + count($warnings);
        $doneItems  = $blockingComplete + $warningComplete;
        $overallPct = $totalItems > 0 ? (int) round($doneItems / $totalItems * 100) : 0;

        $mappedScreens = $screens->filter(fn($s) => !empty($s->figma_frame_id))->count();

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
            'mapped_screens'    => $mappedScreens,
        ];
    }

    private function checkBlocking($artifacts): array
    {
        $blockingTypes = [
            ArtifactType::DESIGN_TOKENS,
            ArtifactType::COMPONENT_SPEC,
            ArtifactType::LAYOUT_SPEC,
            ArtifactType::DESIGN_SYSTEM_DOC,
            ArtifactType::DEV_HANDOFF,
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

    private function checkWarnings($artifacts, $screens, int $totalScreens): array
    {
        $result = [];

        // Screen Mapping coverage
        $mappedScreens = $screens->filter(fn($s) => !empty($s->figma_frame_id))->count();
        $mappingPct    = $totalScreens > 0 ? $mappedScreens / $totalScreens : 0;
        $result[] = [
            'type'     => 'screen_mapping',
            'label'    => 'Figma 화면 매핑',
            'complete' => $totalScreens === 0 || $mappingPct >= self::MAPPING_THRESHOLD,
            'artifact' => null,
            'coverage' => $totalScreens > 0 ? (int) round($mappingPct * 100) : null,
            'covered'  => $mappedScreens,
            'total'    => $totalScreens,
            'note'     => '화면 70% 이상 Figma 매핑 권장',
        ];

        // Design Review score
        $reviewArtifact = $artifacts->get(ArtifactType::DESIGN_REVIEW->value);
        $reviewScore    = null;
        $reviewComplete = false;
        if ($reviewArtifact) {
            $reviewScore    = $reviewArtifact->meta['compliance_score'] ?? null;
            $reviewComplete = $reviewScore !== null && $reviewScore >= self::REVIEW_SCORE_THRESHOLD;
        }
        $result[] = [
            'type'     => ArtifactType::DESIGN_REVIEW->value,
            'label'    => ArtifactType::DESIGN_REVIEW->label(),
            'complete' => $reviewComplete,
            'artifact' => $reviewArtifact,
            'coverage' => $reviewScore,
            'covered'  => null,
            'total'    => null,
            'note'     => '일관성 점수 60점 이상 권장',
        ];

        return $result;
    }
}
