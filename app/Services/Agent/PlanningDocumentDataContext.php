<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentGap;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\Schedule;

class PlanningDocumentDataContext
{
    /**
     * Build the full data context array for template rendering and T22 웍스 generation.
     *
     * @return array{project: mixed, asis: array|null, tobe: array|null, requirements: \Illuminate\Support\Collection, gap: array|null, gaps: \Illuminate\Support\Collection, screens: \Illuminate\Support\Collection, milestones: \Illuminate\Support\Collection, attached_files: \Illuminate\Support\Collection, document_version: string, ai_sections: array}
     */
    public function build(int $projectId): array
    {
        return [
            'project'          => $this->loadProject($projectId),
            'asis'             => $this->loadAsIs($projectId),
            'tobe'             => $this->loadToBe($projectId),
            'requirements'     => $this->loadRequirements($projectId),
            'gap'              => $this->loadGap($projectId),
            'gaps'             => $this->loadGaps($projectId),
            'screens'          => $this->loadScreens($projectId),
            'milestones'       => $this->loadMilestones($projectId),
            'attached_files'   => $this->loadAttachedFiles($projectId),
            'document_version' => '1.0',
            'ai_sections'      => [], // filled during T22 generation
        ];
    }

    /**
     * Returns labels of data inputs that are missing for document generation.
     * Required: AS-IS, TO-BE, Gap. Optional: screens.
     *
     * @return string[]
     */
    public function getMissingData(int $projectId): array
    {
        $missing = [];

        if (!$this->hasAsIs($projectId)) {
            $missing[] = 'AS-IS 분석';
        }
        if (!$this->hasToBe($projectId)) {
            $missing[] = 'TO-BE 요구사항';
        }
        if (!$this->hasGap($projectId)) {
            $missing[] = 'Gap 분석';
        }

        return $missing;
    }

    /**
     * Returns a status map used by the template preview UI.
     *
     * @return array<string, array{ready: bool, count: int|null, label: string, route_key: string, optional: bool}>
     */
    public function getDataStatus(int $projectId): array
    {
        $asIsArtifact = $this->getAsIsArtifact($projectId);
        $asIsContent  = $asIsArtifact ? (json_decode($asIsArtifact->content ?? '{}', true) ?? []) : [];
        $issueCount   = count($asIsContent['issues'] ?? []);

        $toBeArtifact = $this->getToBeArtifact($projectId);
        $reqCount     = AiAgentRequirement::where('project_id', $projectId)->count();

        $gapArtifact = $this->getGapArtifact($projectId);
        $gapContent  = $gapArtifact ? (json_decode($gapArtifact->content ?? '{}', true) ?? []) : [];
        $gapCount    = AiAgentGap::where('project_id', $projectId)->count();

        $screenCount = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->count();

        return [
            'asis' => [
                'ready'     => $asIsArtifact !== null && !empty($asIsArtifact->content),
                'count'     => $issueCount,
                'count_label' => '이슈',
                'label'     => 'AS-IS 분석',
                'route_key' => 'as-is',
                'optional'  => false,
            ],
            'tobe' => [
                'ready'     => $toBeArtifact !== null && $reqCount > 0,
                'count'     => $reqCount,
                'count_label' => '요구사항',
                'label'     => 'TO-BE 요구사항',
                'route_key' => 'to-be',
                'optional'  => false,
            ],
            'gap' => [
                'ready'     => $gapArtifact !== null && !empty($gapArtifact->content),
                'count'     => $gapCount,
                'count_label' => 'Gap',
                'label'     => 'Gap 분석',
                'route_key' => 'gap',
                'optional'  => false,
            ],
            'screens' => [
                'ready'     => $screenCount > 0,
                'count'     => $screenCount,
                'count_label' => '화면',
                'label'     => '화면 목록',
                'route_key' => 'screens',
                'optional'  => true,
            ],
        ];
    }

    public function hasAsIs(int $projectId): bool
    {
        $artifact = $this->getAsIsArtifact($projectId);
        return $artifact !== null && !empty($artifact->content);
    }

    public function hasToBe(int $projectId): bool
    {
        $artifact = $this->getToBeArtifact($projectId);
        return $artifact !== null && AiAgentRequirement::where('project_id', $projectId)->exists();
    }

    public function hasGap(int $projectId): bool
    {
        $artifact = $this->getGapArtifact($projectId);
        return $artifact !== null && !empty($artifact->content);
    }

    private function loadProject(int $projectId): ?Project
    {
        return Project::with(['members'])->find($projectId);
    }

    private function loadAsIs(int $projectId): ?array
    {
        $artifact = $this->getAsIsArtifact($projectId);
        if (!$artifact) {
            return null;
        }
        return json_decode($artifact->content ?? '{}', true) ?? [];
    }

    private function loadToBe(int $projectId): ?array
    {
        $artifact = $this->getToBeArtifact($projectId);
        if (!$artifact) {
            return null;
        }
        return json_decode($artifact->content ?? '{}', true) ?? [];
    }

    private function loadRequirements(int $projectId): \Illuminate\Support\Collection
    {
        return AiAgentRequirement::where('project_id', $projectId)
            ->orderBy('req_id')
            ->get();
    }

    private function loadGap(int $projectId): ?array
    {
        $artifact = $this->getGapArtifact($projectId);
        if (!$artifact) {
            return null;
        }
        return json_decode($artifact->content ?? '{}', true) ?? [];
    }

    private function loadGaps(int $projectId): \Illuminate\Support\Collection
    {
        return AiAgentGap::where('project_id', $projectId)
            ->orderBy('gap_id')
            ->get();
    }

    private function loadScreens(int $projectId): \Illuminate\Support\Collection
    {
        return AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->orderBy('order')
            ->get();
    }

    private function loadMilestones(int $projectId): \Illuminate\Support\Collection
    {
        return Schedule::where('project_id', $projectId)
            ->orderBy('start_date')
            ->get();
    }

    private function loadAttachedFiles(int $projectId): \Illuminate\Support\Collection
    {
        return \App\Models\Agent\AiAgentArtifactFile::whereHas('artifact', fn($q) => $q->where('project_id', $projectId))
            ->get();
    }

    private function getAsIsArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::AS_IS_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    private function getToBeArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::TO_BE_REQUIREMENTS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    private function getGapArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::GAP_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }
}
