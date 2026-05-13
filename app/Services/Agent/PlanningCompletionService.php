<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;

class PlanningCompletionService
{
    private const SCREEN_PROMPT_THRESHOLD = 0.80;
    private const MOCKUP_THRESHOLD        = 0.50;

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

        $totalItems  = count($blocking) + count($warnings);
        $doneItems   = $blockingComplete + $warningComplete;
        $overallPct  = $totalItems > 0 ? (int) round($doneItems / $totalItems * 100) : 0;

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
            'screens_with_prompt' => $screens->filter(fn($s) => !empty($s->generation_prompt))->count(),
            'screens_with_mockup' => $screens->filter(fn($s) => !empty($s->mockup_content))->count(),
        ];
    }

    private function checkBlocking($artifacts): array
    {
        $blockingTypes = [
            ArtifactType::AS_IS_ANALYSIS,
            ArtifactType::TO_BE_REQUIREMENTS,
            ArtifactType::GAP_ANALYSIS,
            ArtifactType::PLANNING_DOC,
        ];

        $result = [];
        foreach ($blockingTypes as $type) {
            $artifact = $artifacts->get($type->value);
            $complete = $artifact !== null && !empty($artifact->content);

            if ($type === ArtifactType::TO_BE_REQUIREMENTS && $complete) {
                $complete = substr_count($artifact->content, 'REQ-') >= 1;
            }

            $result[] = [
                'type'     => $type->value,
                'label'    => $type->label(),
                'complete' => $complete,
                'artifact' => $artifact,
                'note'     => $type === ArtifactType::TO_BE_REQUIREMENTS
                    ? 'REQ-XXX 형식 요구사항 1개 이상 필요'
                    : null,
            ];
        }

        return $result;
    }

    private function checkWarnings($artifacts, $screens, int $totalScreens): array
    {
        $result = [];

        // IA Flow
        $iaArtifact = $artifacts->get(ArtifactType::IA_FLOW->value);
        $result[] = [
            'type'     => ArtifactType::IA_FLOW->value,
            'label'    => ArtifactType::IA_FLOW->label(),
            'complete' => $iaArtifact !== null && !empty($iaArtifact->content),
            'artifact' => $iaArtifact,
            'coverage' => null,
            'covered'  => null,
            'total'    => null,
            'note'     => null,
        ];

        // Screen Prompts coverage
        $screensWithPrompt = $screens->filter(fn($s) => !empty($s->generation_prompt))->count();
        $promptPct         = $totalScreens > 0 ? $screensWithPrompt / $totalScreens : 0;
        $result[] = [
            'type'     => ArtifactType::SCREEN_PROMPTS->value,
            'label'    => ArtifactType::SCREEN_PROMPTS->label(),
            'complete' => $totalScreens === 0 || $promptPct >= self::SCREEN_PROMPT_THRESHOLD,
            'artifact' => null,
            'coverage' => $totalScreens > 0 ? (int) round($promptPct * 100) : null,
            'covered'  => $screensWithPrompt,
            'total'    => $totalScreens,
            'note'     => '화면 80% 이상 커버리지 권장',
        ];

        // Mockup coverage
        $screensWithMockup = $screens->filter(fn($s) => !empty($s->mockup_content))->count();
        $mockupPct         = $totalScreens > 0 ? $screensWithMockup / $totalScreens : 0;
        $result[] = [
            'type'     => ArtifactType::MOCKUP->value,
            'label'    => ArtifactType::MOCKUP->label(),
            'complete' => $totalScreens === 0 || $mockupPct >= self::MOCKUP_THRESHOLD,
            'artifact' => null,
            'coverage' => $totalScreens > 0 ? (int) round($mockupPct * 100) : null,
            'covered'  => $screensWithMockup,
            'total'    => $totalScreens,
            'note'     => '화면 50% 이상 커버리지 권장',
        ];

        return $result;
    }
}
