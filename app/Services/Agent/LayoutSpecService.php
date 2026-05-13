<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Figma\LayoutAnalyzer;
use App\Services\Agent\Figma\LayoutSpecSet;
use App\Services\Agent\Figma\FigmaClientFactory;

class LayoutSpecService
{
    public function __construct(
        private readonly FigmaClientFactory $clientFactory,
    ) {}

    public function extractFromFigma(
        Project $project,
        string  $figmaFileKey,
        User    $user,
    ): array {
        $client  = $this->clientFactory->forUser($user);
        $specSet = (new LayoutAnalyzer($client))->analyze($figmaFileKey);

        $file     = $client->getFile($figmaFileKey);
        $fileName = $file->name;

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        $stats = $specSet->getStats();

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::LAYOUT_SPEC,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "표준 레이아웃 — {$project->name}",
            content:   $specSet->toJson(),
            userId:    $user->id,
            meta: [
                'figma_file_key'              => $figmaFileKey,
                'figma_file_name'             => $fileName,
                'total_frames_analyzed'       => $stats['total_frames_analyzed'],
                'standard_layouts_identified' => $stats['standard_layouts_identified'],
                'non_standard_frames'         => $stats['non_standard_frames'],
                'extracted_at'                => now()->toIso8601String(),
            ],
        );

        return compact('artifact', 'specSet');
    }

    public function getCurrent(Project $project): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::LAYOUT_SPEC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    public function parseSpecSet(AiAgentArtifact $artifact): ?LayoutSpecSet
    {
        if (empty($artifact->content)) return null;

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (!$data) return null;

        $specSet = new LayoutSpecSet(
            standardLayouts:     $data['standard_layouts']    ?? [],
            spacingScale:        $data['spacing_scale']       ?? [],
            nonStandardFrames:   $data['non_standard_frames'] ?? [],
            totalFramesAnalyzed: $data['$metadata']['stats']['total_frames_analyzed'] ?? 0,
        );

        if (isset($data['$metadata'])) {
            $specSet->setMetadata($data['$metadata']);
        }

        return $specSet;
    }

    public function updateStandardLayout(
        AiAgentArtifact $artifact,
        string          $layoutKey,
        array           $changes,
        int             $userId,
    ): AiAgentArtifact {
        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        $allowed = ['name', 'description'];
        if (isset($data['standard_layouts'][$layoutKey])) {
            foreach ($allowed as $field) {
                if (array_key_exists($field, $changes)) {
                    $data['standard_layouts'][$layoutKey][$field] = $changes[$field];
                }
            }
        }

        $artifact->updateWithVersion(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $userId,
            "레이아웃 표준 '{$layoutKey}' 편집",
        );

        return $artifact->fresh();
    }
}
