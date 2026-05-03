<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactVersion;
use Illuminate\Support\Collection;

class VersioningService
{
    /**
     * 산출물 내용을 업데이트하면서 현재 버전을 스냅샷으로 보관.
     * 내용이 동일하면 새 버전을 만들지 않는다.
     */
    public function update(AiAgentArtifact $artifact, array $content, int $updatedBy): AiAgentArtifact
    {
        if ($artifact->content === $content) {
            return $artifact;
        }

        // 기존 내용 스냅샷
        if (!empty($artifact->content)) {
            AiAgentArtifactVersion::create([
                'artifact_id' => $artifact->id,
                'version'     => $artifact->version,
                'content'     => $artifact->content,
                'created_by'  => $artifact->updated_by ?? $updatedBy,
            ]);
        }

        $artifact->update([
            'content'    => $content,
            'version'    => $artifact->version + 1,
            'updated_by' => $updatedBy,
            'status'     => 'draft',
        ]);

        return $artifact->fresh();
    }

    /**
     * 특정 산출물의 버전 이력 목록 (최신순).
     *
     * @return Collection<int, AiAgentArtifactVersion>
     */
    public function history(AiAgentArtifact $artifact): Collection
    {
        return AiAgentArtifactVersion::where('artifact_id', $artifact->id)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * 특정 버전의 스냅샷 조회.
     */
    public function getVersion(AiAgentArtifact $artifact, int $version): ?AiAgentArtifactVersion
    {
        return AiAgentArtifactVersion::where('artifact_id', $artifact->id)
            ->where('version', $version)
            ->first();
    }

    /**
     * 특정 버전으로 산출물 내용 복구.
     * 복구 전 현재 상태도 스냅샷 보관.
     */
    public function restore(AiAgentArtifact $artifact, int $targetVersion, int $restoredBy): AiAgentArtifact
    {
        $snapshot = $this->getVersion($artifact, $targetVersion);

        if (!$snapshot) {
            throw new \InvalidArgumentException("Version {$targetVersion} not found for artifact {$artifact->id}");
        }

        return $this->update($artifact, $snapshot->content, $restoredBy);
    }

    /**
     * 가장 최근 버전 스냅샷 조회.
     */
    public function latest(AiAgentArtifact $artifact): ?AiAgentArtifactVersion
    {
        return AiAgentArtifactVersion::where('artifact_id', $artifact->id)
            ->orderByDesc('version')
            ->first();
    }
}
