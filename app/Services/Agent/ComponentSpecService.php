<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Figma\ComponentExtractor;
use App\Services\Agent\Figma\ComponentSpecSet;
use App\Services\Agent\Figma\DesignTokenSet;
use App\Services\Agent\Figma\FigmaClientFactory;

class ComponentSpecService
{
    public function __construct(
        private readonly FigmaClientFactory $clientFactory,
    ) {}

    public function extractFromFigma(
        Project $project,
        string  $figmaFileKey,
        User    $user,
    ): array {
        $client = $this->clientFactory->forUser($user);

        // T28 토큰 산출물이 있으면 매핑에 활용
        $tokenSet = $this->getTokenSet($project);

        $specSet = (new ComponentExtractor($client))->extract($figmaFileKey, $tokenSet);

        // 파일명 조회
        $file     = $client->getFile($figmaFileKey);
        $fileName = $file->name;

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        $stats = $specSet->getStats();

        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::COMPONENT_SPEC,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "Component 명세서 — {$project->name}",
            content:   $specSet->toJson(),
            userId:    $user->id,
            meta: [
                'figma_file_key'       => $figmaFileKey,
                'figma_file_name'      => $fileName,
                'total_components'     => $stats['total_components'],
                'component_sets'       => $stats['component_sets'],
                'single_components'    => $stats['single_components'],
                'total_variants'       => $stats['total_variants'],
                'token_artifact_id'    => $this->getTokensArtifactId($project),
                'extracted_at'         => now()->toIso8601String(),
            ],
        );

        return compact('artifact', 'specSet');
    }

    public function getCurrent(Project $project): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::COMPONENT_SPEC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    public function parseSpecSet(AiAgentArtifact $artifact): ?ComponentSpecSet
    {
        if (empty($artifact->content)) return null;

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (!$data) return null;

        $specSet = new ComponentSpecSet();

        if (isset($data['$metadata'])) {
            $specSet->setMetadata($data['$metadata']);
        }

        foreach ($data['components'] ?? [] as $key => $component) {
            $specSet->addComponent($key, $component);
        }

        return $specSet;
    }

    public function updateComponent(
        AiAgentArtifact $artifact,
        string          $componentKey,
        array           $changes,
        int             $userId,
    ): AiAgentArtifact {
        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        // 허용 필드만 업데이트
        $allowed = ['description', 'documentation'];
        if (isset($data['components'][$componentKey])) {
            foreach ($allowed as $field) {
                if (array_key_exists($field, $changes)) {
                    $data['components'][$componentKey][$field] = $changes[$field];
                }
            }
        }

        $artifact->updateWithVersion(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $userId,
            "컴포넌트 '{$componentKey}' 편집",
        );

        return $artifact->fresh();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getTokenSet(Project $project): ?DesignTokenSet
    {
        $tokensArtifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_TOKENS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        if (!$tokensArtifact || empty($tokensArtifact->content)) {
            return null;
        }

        $data = is_array($tokensArtifact->content)
            ? $tokensArtifact->content
            : json_decode($tokensArtifact->content, true);

        if (!$data) return null;

        // DesignTokenExtractor의 addStyleKeyMapping은 런타임에만 생성됨 →
        // 저장된 JSON에는 styleKeyMap이 없으므로 null 반환 (매핑 없이 추출)
        return null;
    }

    private function getTokensArtifactId(Project $project): ?int
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_TOKENS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->value('id');
    }
}
