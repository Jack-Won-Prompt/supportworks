<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use Illuminate\Support\Str;

class MigrationGuideDataContext
{
    public function build(int $projectId): array
    {
        $project = Project::find($projectId);
        $config  = ProjectAiAgentConfig::forProject($projectId);

        return [
            'project'     => [
                'id'             => $project?->id,
                'name'           => $project?->name ?? 'Unknown Project',
                'db_name'        => Str::slug($project?->name ?? 'project', '_') . '_db',
                'frontend_stack' => strtoupper($config?->frontend_stack?->value ?? 'html'),
            ],
            'database'    => $this->loadDatabaseInfo($projectId),
            'rbac'        => $this->loadRbacInfo($projectId),
            'admin_setup' => [
                'recommended_admin_email'           => 'admin@example.com',
                'recommended_admin_password_policy' => 'min:12, 영문/숫자/특수문자 혼합',
            ],
            'metadata'    => [
                'generated_at' => now(),
                'version'      => '1.0',
                'mode'         => 'fresh_install',
            ],
        ];
    }

    private function loadDatabaseInfo(int $projectId): array
    {
        $erd     = $this->latest($projectId, ArtifactType::ERD);
        if (!$erd) return ['tables' => [], 'has_relationships' => false, 'has_erd' => false];

        $content = json_decode($erd->content, true) ?? [];
        $tables  = array_map(fn($t) => [
            'name'          => $t['name'] ?? '',
            'description'   => $t['description'] ?? '',
            'columns_count' => count($t['columns'] ?? []),
        ], $content['tables'] ?? []);

        return [
            'has_erd'           => true,
            'tables'            => $tables,
            'has_relationships' => !empty($content['relationships']),
        ];
    }

    private function loadRbacInfo(int $projectId): array
    {
        $rbac = $this->latest($projectId, ArtifactType::RBAC_MODEL);
        if (!$rbac) return ['roles' => [], 'admin_role_key' => null, 'has_rbac' => false];

        $content = json_decode($rbac->content, true) ?? [];
        $roles   = $content['roles'] ?? [];

        return [
            'has_rbac'       => true,
            'roles'          => $roles,
            'admin_role_key' => $this->detectAdminRole($roles),
        ];
    }

    private function detectAdminRole(array $roles): ?string
    {
        // key 또는 name 필드에서 'admin', '관리자' 키워드 탐색
        foreach ($roles as $role) {
            $key  = strtolower($role['key']  ?? '');
            $name = strtolower($role['name'] ?? '');
            if (str_contains($key, 'admin') || str_contains($name, 'admin') || str_contains($name, '관리자')) {
                return $role['key'] ?? $role['name'] ?? null;
            }
        }
        // 첫 번째 역할 반환
        return $roles[0]['key'] ?? $roles[0]['name'] ?? null;
    }

    private function latest(int $projectId, ArtifactType $type): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', $type->value)
            ->latest('created_at')
            ->first();
    }
}
