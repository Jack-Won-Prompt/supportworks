<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;

class DeployGuideDataContext
{
    public function build(int $projectId): array
    {
        $project = Project::find($projectId);
        $config  = ProjectAiAgentConfig::forProject($projectId);

        return [
            'project'  => $this->buildProjectSection($project, $config),
            'database' => $this->buildDatabaseSection($projectId),
            'api'      => $this->buildApiSection($projectId),
            'frontend' => $this->buildFrontendSection($projectId, $config),
            'backend'  => $this->buildBackendSection($projectId),
            'integration' => $this->buildIntegrationSection($projectId),
            'package'  => $this->buildPackageSection($projectId),
            'metadata' => [
                'generated_at'     => now()->format('Y-m-d H:i'),
                'ai_agent_version' => '1.0',
            ],
        ];
    }

    private function buildProjectSection(?Project $project, ?ProjectAiAgentConfig $config): array
    {
        return [
            'id'             => $project?->id,
            'name'           => $project?->name ?? 'Unknown Project',
            'frontend_stack' => $config?->frontend_stack?->value ?? 'html',
            'backend_stack'  => 'laravel',
            'description'    => $project?->description ?? '',
        ];
    }

    private function buildDatabaseSection(int $projectId): array
    {
        $erd      = $this->latest($projectId, ArtifactType::ERD);
        $erdData  = $erd ? (json_decode($erd->content, true) ?? []) : [];
        $tables   = $erdData['tables'] ?? [];

        return [
            'tables_count' => count($tables),
            'tables'       => array_map(fn($t) => $t['name'] ?? '', $tables),
            'sql_file'     => '03-dev-prep/erd.sql',
            'has_erd'      => $erd !== null,
        ];
    }

    private function buildApiSection(int $projectId): array
    {
        $apiSpec = $this->latest($projectId, ArtifactType::API_SPEC);
        $apiData = $apiSpec ? (json_decode($apiSpec->content, true) ?? []) : [];
        $endpoints = $apiData['endpoints'] ?? [];

        return [
            'endpoints_count' => count($endpoints),
            'spec_file'       => '03-dev-prep/api-spec.yaml',
            'has_spec'        => $apiSpec !== null,
        ];
    }

    private function buildFrontendSection(int $projectId, ?ProjectAiAgentConfig $config): array
    {
        $screenCount = AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->count();

        $codeCount = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->count();

        $stack = $config?->frontend_stack?->value ?? 'html';

        return [
            'stack'       => $stack,
            'screens_count' => $screenCount,
            'code_count'  => $codeCount,
            'folder_path' => '04-frontend/',
            'package_manager' => $stack === 'html' ? null : 'npm',
            'dev_command' => match($stack) {
                'react' => 'npm run dev',
                'vue'   => 'npm run dev',
                default => null,
            },
            'build_command' => match($stack) {
                'react' => 'npm run build',
                'vue'   => 'npm run build',
                default => null,
            },
        ];
    }

    private function buildBackendSection(int $projectId): array
    {
        $resourceCount = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->count();

        $rbac    = $this->latest($projectId, ArtifactType::RBAC_MODEL);
        $rbacData = $rbac ? (json_decode($rbac->content, true) ?? []) : [];
        $roleCount = count($rbacData['roles'] ?? []);

        return [
            'framework'      => 'Laravel',
            'resources_count'=> $resourceCount,
            'folder_path'    => '05-backend/',
            'role_count'     => $roleCount,
            'has_rbac'       => $rbac !== null,
            'policy_file'    => '03-dev-prep/RolePolicy.php',
        ];
    }

    private function buildIntegrationSection(int $projectId): array
    {
        $integration = $this->latest($projectId, ArtifactType::API_INTEGRATION);
        $intData     = $integration ? (json_decode($integration->content, true) ?? []) : [];
        $compliance  = $intData['analysis']['$metadata']['compliance_rate'] ?? null;

        $review      = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'project')
            ->latest('created_at')
            ->first();
        $reviewData  = $review ? (json_decode($review->content, true) ?? []) : [];

        return [
            'has_integration'   => $integration !== null,
            'compliance_rate'   => $compliance,
            'review_score'      => $reviewData['overall_score'] ?? null,
            'folder_path'       => '06-integration/',
        ];
    }

    private function buildPackageSection(int $projectId): array
    {
        $artifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RELEASE_PACKAGE->value)
            ->latest('created_at')
            ->first();

        $content = $artifact ? (json_decode($artifact->content, true) ?? []) : [];
        $path    = $content['package_path'] ?? null;

        return [
            'has_package'  => $artifact !== null,
            'package_path' => $path,
            'size_mb'      => ($path && file_exists($path))
                ? round(filesize($path) / 1024 / 1024, 2)
                : null,
            'generated_at' => $artifact?->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function latest(int $projectId, ArtifactType $type): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', $type->value)
            ->latest('created_at')
            ->first();
    }
}
