<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Figma\DesignTokenExtractor;
use App\Services\Agent\Figma\DesignTokenSet;
use App\Services\Agent\Figma\FigmaClientFactory;

class DesignTokenService
{
    public function __construct(
        private readonly FigmaClientFactory $clientFactory,
    ) {}

    public function extractFromFigma(
        Project $project,
        string  $figmaFileKey,
        User    $user,
    ): array {
        $client   = $this->clientFactory->forUser($user);
        $tokenSet = (new DesignTokenExtractor($client))->extract($figmaFileKey);

        // 파일 메타 조회 (파일명)
        $file     = $client->getFile($figmaFileKey);
        $fileName = $file->name;

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::DESIGN_TOKENS,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "Design Tokens — {$project->name}",
            content:   $tokenSet->toJson(),
            userId:    $user->id,
            meta: [
                'figma_file_key'  => $figmaFileKey,
                'figma_file_name' => $fileName,
                'token_count'     => $tokenSet->getTokenCount(),
                'color_count'     => $tokenSet->getCategoryCount('color'),
                'typography_count'=> $tokenSet->getCategoryCount('typography'),
                'shadow_count'    => $tokenSet->getCategoryCount('shadow'),
                'layout_count'    => $tokenSet->getCategoryCount('layout'),
                'extracted_at'    => now()->toIso8601String(),
            ],
        );

        return compact('artifact', 'tokenSet');
    }

    public function getCurrent(Project $project): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_TOKENS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    public function parseTokenSet(AiAgentArtifact $artifact): ?DesignTokenSet
    {
        if (empty($artifact->content)) return null;

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (!$data) return null;

        $tokenSet = new DesignTokenSet();
        if (isset($data['$metadata'])) {
            $tokenSet->addMetadata($data['$metadata']);
        }

        // 편집된 JSON을 tokenSet으로 재구성 (색상/타이포/그림자/레이아웃)
        foreach (['color', 'typography', 'shadow', 'layout'] as $cat) {
            if (!isset($data[$cat])) continue;
            $this->rehydrateCategory($tokenSet, $cat, $data[$cat], []);
        }

        return $tokenSet;
    }

    private function rehydrateCategory(DesignTokenSet $tokenSet, string $cat, array $node, array $path): void
    {
        if (isset($node['$value'])) {
            $tokenSet->addToken($cat, $path, $node['$value'], $node['$type'] ?? 'unknown', $node['$description'] ?? null);
            return;
        }
        foreach ($node as $key => $child) {
            if (str_starts_with((string) $key, '$')) continue;
            if (is_array($child)) {
                $this->rehydrateCategory($tokenSet, $cat, $child, [...$path, $key]);
            }
        }
    }
}
