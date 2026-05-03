<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentPrompt;
use Illuminate\Database\Eloquent\Collection;

class PromptLibraryService
{
    /**
     * 프롬프트 등록 또는 업데이트 (name + project_id 기준 upsert).
     */
    public function upsert(
        string  $name,
        string  $template,
        string  $stage,
        string  $taskType,
        int     $createdBy,
        ?int    $projectId = null,
        ?array  $variables = null,
        int     $version = 1
    ): AiAgentPrompt {
        $existing = AiAgentPrompt::where('name', $name)
            ->where('project_id', $projectId)
            ->orderByDesc('version')
            ->first();

        if ($existing && $existing->template === $template) {
            return $existing;
        }

        $newVersion = $existing ? $existing->version + 1 : $version;

        // 기존 버전 비활성화
        if ($existing) {
            AiAgentPrompt::where('name', $name)
                ->where('project_id', $projectId)
                ->update(['is_active' => false]);
        }

        return AiAgentPrompt::create([
            'project_id' => $projectId,
            'stage'      => $stage,
            'task_type'  => $taskType,
            'name'       => $name,
            'template'   => $template,
            'variables'  => $variables,
            'version'    => $newVersion,
            'is_active'  => true,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * 특정 단계·태스크 프롬프트 조회 및 변수 렌더링.
     * 프로젝트 전용 → 공통 순으로 fallback.
     */
    public function render(
        string $stage,
        string $taskType,
        array  $values = [],
        ?int   $projectId = null
    ): ?string {
        $prompt = AiAgentPrompt::resolve($stage, $taskType, $projectId);

        return $prompt?->render($values);
    }

    /**
     * 단계별 활성 프롬프트 목록.
     *
     * @return Collection<int, AiAgentPrompt>
     */
    public function listByStage(string $stage, ?int $projectId = null): Collection
    {
        return AiAgentPrompt::active()
            ->forStage($stage)
            ->when($projectId, fn($q) => $q->where(
                fn($q2) => $q2->where('project_id', $projectId)->orWhereNull('project_id')
            ), fn($q) => $q->whereNull('project_id'))
            ->get();
    }

    /**
     * 이름 기준으로 전체 버전 이력 조회.
     *
     * @return Collection<int, AiAgentPrompt>
     */
    public function versionHistory(string $name, ?int $projectId = null): Collection
    {
        return AiAgentPrompt::where('name', $name)
            ->where('project_id', $projectId)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * 특정 버전으로 복구 (해당 버전을 활성화하고 나머지 비활성화).
     */
    public function restoreVersion(string $name, int $version, ?int $projectId = null): ?AiAgentPrompt
    {
        $target = AiAgentPrompt::where('name', $name)
            ->where('project_id', $projectId)
            ->where('version', $version)
            ->first();

        if (!$target) {
            return null;
        }

        AiAgentPrompt::where('name', $name)
            ->where('project_id', $projectId)
            ->update(['is_active' => false]);

        $target->update(['is_active' => true]);

        return $target->fresh();
    }

    /**
     * 프롬프트 삭제 (비활성화).
     */
    public function deactivate(int $promptId): bool
    {
        return (bool) AiAgentPrompt::where('id', $promptId)->update(['is_active' => false]);
    }
}
