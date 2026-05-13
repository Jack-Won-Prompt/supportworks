<?php

namespace App\Services\PromptBuilder;

use App\Models\PromptBuilder\Builder;
use App\Models\PromptBuilder\StandardAsset;

class StandardMappingService
{
    public function createMapping(
        int $workspaceId,
        ?array $figmaAnalysis,
        string $purposeType,
        array $targets,
    ): array {
        $assetTypes = $this->resolveAssetTypes($purposeType, $targets);

        $appliedStandards = StandardAsset::where('workspace_id', $workspaceId)
            ->whereIn('asset_type', $assetTypes)
            ->where('is_active', true)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'asset_type' => $a->asset_type,
                'version'    => $a->version,
                'content'    => $a->content,
            ])
            ->toArray();

        $candidates = $this->detectCandidates($figmaAnalysis, $appliedStandards);

        return [
            'workspace_id'      => $workspaceId,
            'applied_standards' => $appliedStandards,
            'candidates'        => $candidates,
            'asset_types'       => $assetTypes,
        ];
    }

    public function findRelatedBuilders(int $projectId, array $mapping): array
    {
        if (empty($mapping['applied_standards'])) {
            return [];
        }

        $standardIds = array_column($mapping['applied_standards'], 'id');

        return Builder::where('project_id', $projectId)
            ->whereJsonContains('applied_standards', ['id' => $standardIds[0]])
            ->limit(10)
            ->get()
            ->map(fn($b) => [
                'id'              => $b->id,
                'title'           => $b->title,
                'dependency_type' => 'uses_standard',
                'strength'        => 'medium',
                'confidence'      => 0.7,
            ])
            ->toArray();
    }

    private function resolveAssetTypes(string $purposeType, array $targets): array
    {
        if (!empty($targets)) {
            return array_map(fn($t) => match ($t) {
                'component' => 'component',
                'css'       => 'css_token',
                'layout'    => 'layout',
                'js'        => 'js_utility',
                default     => $t,
            }, $targets);
        }

        return match ($purposeType) {
            'standard_assets'   => ['layout', 'component', 'css_token', 'js_utility'],
            'screen_generation' => ['layout', 'component', 'css_token'],
            'sequence_step'     => ['component', 'js_utility'],
            default             => ['component'],
        };
    }

    private function detectCandidates(?array $figmaAnalysis, array $appliedStandards): array
    {
        if (empty($figmaAnalysis['components'])) {
            return [];
        }

        $standardNames = array_column($appliedStandards, 'name');
        $candidates    = [];

        foreach ($figmaAnalysis['components'] as $component) {
            $name = $component['name'];

            $matched = false;
            foreach ($standardNames as $stdName) {
                if (stripos($name, $stdName) !== false || stripos($stdName, $name) !== false) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $candidates[] = [
                    'name'       => $name,
                    'asset_type' => 'component',
                    'content'    => "// Auto-detected from Figma: {$name}",
                    'metadata'   => ['figma_id' => $component['id'] ?? null],
                ];
            }
        }

        return $candidates;
    }
}
