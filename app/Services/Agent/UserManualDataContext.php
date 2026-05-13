<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentTraceabilityLink;
use App\Models\Project;

class UserManualDataContext
{
    public function build(int $projectId): array
    {
        $project = Project::find($projectId);

        $screens = AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->orderBy('order')
            ->orderBy('screen_id')
            ->get();

        return [
            'project'  => [
                'id'          => $project?->id,
                'name'        => $project?->name ?? 'Unknown Project',
                'description' => $project?->description ?? '',
            ],
            'screens'  => $this->buildScreens($projectId, $screens),
            'roles'    => $this->loadRoles($projectId),
            'stats'    => [
                'screen_count'     => $screens->count(),
                'figma_mapped'     => $screens->filter(fn($s) => $s->isMappedToFigma())->count(),
                'requirement_count'=> AiAgentRequirement::where('project_id', $projectId)->count(),
            ],
            'metadata' => [
                'generated_at' => now(),
                'version'      => '1.0',
            ],
        ];
    }

    private function buildScreens(int $projectId, $screens): array
    {
        $all    = $screens->values();
        $result = [];

        foreach ($all as $idx => $screen) {
            $prev = $idx > 0 ? $all[$idx - 1] : null;
            $next = $idx < $all->count() - 1 ? $all[$idx + 1] : null;

            $result[] = [
                'id'                   => $screen->screen_id,
                'title'                => $screen->title,
                'description'          => $screen->description ?? '',
                'requirements'         => $this->loadRelatedRequirements($projectId, $screen),
                'figma_url'            => $screen->getFigmaViewUrl(),
                'figma_frame_id'       => $screen->figma_frame_id,
                'figma_file_key'       => $screen->figma_file_key,
                'is_figma_mapped'      => $screen->isMappedToFigma(),
                'figma_image_url'      => null, // enriched by UserManualService
                'required_permissions' => $this->loadScreenPermissions($projectId, $screen->screen_id),
                'flow'                 => [
                    'previous' => $prev ? ['id' => $prev->screen_id, 'title' => $prev->title] : null,
                    'next'     => $next ? ['id' => $next->screen_id, 'title' => $next->title] : null,
                ],
            ];
        }

        return $result;
    }

    private function loadRelatedRequirements(int $projectId, AiAgentScreen $screen): array
    {
        // requirement→screen 방향: source=requirement, target=screen
        $reqIds = AiAgentTraceabilityLink::where('project_id', $projectId)
            ->where('source_type', 'requirement')
            ->where('target_type', 'screen')
            ->where('target_id', $screen->id)
            ->pluck('source_id')
            ->all();

        if (empty($reqIds)) {
            // 역방향도 체크 (source=screen, target=requirement)
            $reqIds = AiAgentTraceabilityLink::where('project_id', $projectId)
                ->where('source_type', 'screen')
                ->where('source_id', $screen->id)
                ->where('target_type', 'requirement')
                ->pluck('target_id')
                ->all();
        }

        if (empty($reqIds)) return [];

        return AiAgentRequirement::whereIn('id', $reqIds)
            ->orderBy('req_id')
            ->get()
            ->map(fn($req) => [
                'id'          => $req->req_id,
                'title'       => $req->title,
                'description' => $req->description ?? '',
                'priority'    => $req->priority?->value ?? 'normal',
            ])
            ->all();
    }

    private function loadRoles(int $projectId): array
    {
        $rbac = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)
            ->latest('created_at')
            ->first();

        if (!$rbac) return [];

        $data = json_decode($rbac->content, true) ?? [];

        return array_map(fn($role) => [
            'name'        => $role['name'] ?? '',
            'description' => $role['description'] ?? '',
        ], $data['roles'] ?? []);
    }

    private function loadScreenPermissions(int $projectId, string $screenId): array
    {
        $rbac = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)
            ->latest('created_at')
            ->first();

        if (!$rbac) return [];

        $data  = json_decode($rbac->content, true) ?? [];
        $perms = $data['permissions'] ?? [];

        $matched = [];
        foreach ($perms as $perm) {
            // resource 이름이 screenId와 일치하거나 포함되는 경우
            $resource = strtolower($perm['resource'] ?? '');
            $sid      = strtolower($screenId);
            if ($resource === $sid || str_contains($resource, $sid) || str_contains($sid, $resource)) {
                $allowed = $perm['allowed_roles'] ?? [];
                if ($allowed) {
                    $matched[] = implode(', ', $allowed) . ' — ' . ($perm['action'] ?? '접근');
                }
            }
        }

        return $matched;
    }
}
