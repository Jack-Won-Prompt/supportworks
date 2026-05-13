<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;

class DesignSystemDataContext
{
    public function build(int $projectId): array
    {
        $project    = Project::find($projectId);
        $tokens     = $this->loadTokens($projectId);
        $components = $this->loadComponents($projectId);
        $layouts    = $this->loadLayouts($projectId);
        $mappings   = $this->loadMappings($projectId);
        $review     = $this->loadReview($projectId);

        return [
            'project'    => $project,
            'tokens'     => $tokens,
            'components' => $components,
            'layouts'    => $layouts,
            'mappings'   => $mappings,
            'review'     => $review,
            'metadata'   => [
                'generated_at' => now(),
                'generator'    => '웍스 Agent v1.0',
                'has_tokens'     => $tokens !== null,
                'has_components' => $components !== null,
                'has_layouts'    => $layouts !== null,
                'has_mappings'   => !empty($mappings),
                'has_review'     => $review !== null,
            ],
        ];
    }

    public function getDataStatus(int $projectId): array
    {
        $tokenArtifact = $this->getArtifact($projectId, ArtifactType::DESIGN_TOKENS);
        $compArtifact  = $this->getArtifact($projectId, ArtifactType::COMPONENT_SPEC);
        $layoutArtifact= $this->getArtifact($projectId, ArtifactType::LAYOUT_SPEC);
        $reviewArtifact= $this->getArtifact($projectId, ArtifactType::DESIGN_REVIEW);

        $tokenData  = $tokenArtifact  ? (json_decode($tokenArtifact->content,  true) ?? []) : [];
        $compData   = $compArtifact   ? (json_decode($compArtifact->content,   true) ?? []) : [];
        $layoutData = $layoutArtifact ? (json_decode($layoutArtifact->content, true) ?? []) : [];

        $tokenCount  = count($tokenData);
        $compCount   = count($compData['components'] ?? []);
        $layoutCount = count($layoutData['standard_layouts'] ?? []);

        $totalScreens  = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->count();
        $mappedScreens = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->whereNotNull('figma_frame_id')->count();

        $reviewData  = $reviewArtifact ? (json_decode($reviewArtifact->content, true) ?? []) : [];
        $reviewScore = $reviewData['$metadata']['stats']['compliance_score'] ?? null;

        return [
            'tokens' => [
                'ready'    => $tokenArtifact !== null && $tokenCount > 0,
                'count'    => $tokenCount,
                'label'    => 'Design Tokens',
                'optional' => false,
            ],
            'components' => [
                'ready'    => $compArtifact !== null && $compCount > 0,
                'count'    => $compCount,
                'label'    => 'Component 명세',
                'optional' => false,
            ],
            'layouts' => [
                'ready'    => $layoutArtifact !== null && $layoutCount > 0,
                'count'    => $layoutCount,
                'label'    => 'Layout / Grid',
                'optional' => false,
            ],
            'mappings' => [
                'ready'    => $mappedScreens > 0,
                'count'    => $mappedScreens,
                'total'    => $totalScreens,
                'label'    => '화면 매핑',
                'optional' => true,
            ],
            'review' => [
                'ready'    => $reviewArtifact !== null,
                'count'    => $reviewScore,
                'label'    => '일관성 검수',
                'optional' => true,
            ],
        ];
    }

    public function getMissingRequired(int $projectId): array
    {
        $status  = $this->getDataStatus($projectId);
        $missing = [];
        foreach (['tokens', 'components', 'layouts'] as $key) {
            if (!$status[$key]['ready']) {
                $missing[] = $status[$key]['label'];
            }
        }
        return $missing;
    }

    // ── Loaders ────────────────────────────────────────────────────────────────

    private function loadTokens(int $projectId): ?array
    {
        $artifact = $this->getArtifact($projectId, ArtifactType::DESIGN_TOKENS);
        return $artifact ? (json_decode($artifact->content, true) ?? []) : null;
    }

    private function loadComponents(int $projectId): ?array
    {
        $artifact = $this->getArtifact($projectId, ArtifactType::COMPONENT_SPEC);
        return $artifact ? (json_decode($artifact->content, true) ?? []) : null;
    }

    private function loadLayouts(int $projectId): ?array
    {
        $artifact = $this->getArtifact($projectId, ArtifactType::LAYOUT_SPEC);
        return $artifact ? (json_decode($artifact->content, true) ?? []) : null;
    }

    private function loadMappings(int $projectId): array
    {
        return AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->orderBy('screen_id')
            ->get()
            ->map(fn($s) => [
                'screen_id'       => $s->screen_id,
                'name'            => $s->title,
                'is_mapped'       => $s->hasFigmaMapping(),
                'figma_frame_name'=> $s->figma_frame_name,
                'figma_url'       => $s->figma_url,
                'figma_dev_url'   => $s->figma_dev_mode_url,
                'applied_layouts' => $s->getAppliedLayouts() ?? [],
            ])
            ->all();
    }

    private function loadReview(int $projectId): ?array
    {
        $artifact = $this->getArtifact($projectId, ArtifactType::DESIGN_REVIEW);
        return $artifact ? (json_decode($artifact->content, true) ?? []) : null;
    }

    private function getArtifact(int $projectId, ArtifactType $type): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', $type->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }
}
