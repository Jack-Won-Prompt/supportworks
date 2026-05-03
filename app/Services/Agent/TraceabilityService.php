<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentTraceabilityLink;
use Illuminate\Support\Collection;

class TraceabilityService
{
    /**
     * 두 아티팩트/엔티티 간 추적성 링크 생성.
     * 이미 동일한 (project, source, target, link_type) 조합이 있으면 기존 레코드 반환.
     */
    public function link(
        int    $projectId,
        string $sourceType,
        int    $sourceId,
        string $sourceRef,
        string $targetType,
        int    $targetId,
        string $targetRef,
        string $linkType = 'implements'
    ): AiAgentTraceabilityLink {
        return AiAgentTraceabilityLink::firstOrCreate(
            [
                'project_id'  => $projectId,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'link_type'   => $linkType,
            ],
            [
                'source_ref' => $sourceRef,
                'target_ref' => $targetRef,
            ]
        );
    }

    /**
     * 특정 소스가 연결한 모든 대상 목록.
     *
     * @return Collection<int, AiAgentTraceabilityLink>
     */
    public function linksFrom(string $sourceType, int $sourceId): Collection
    {
        return AiAgentTraceabilityLink::fromSource($sourceType, $sourceId)->get();
    }

    /**
     * 특정 대상을 참조하는 모든 소스 목록.
     *
     * @return Collection<int, AiAgentTraceabilityLink>
     */
    public function linksTo(string $targetType, int $targetId): Collection
    {
        return AiAgentTraceabilityLink::toTarget($targetType, $targetId)->get();
    }

    /**
     * 영향 분석: 주어진 노드를 직접·간접으로 참조하는 모든 소스 수집 (BFS, depth 제한).
     *
     * @return array<int, array{type: string, id: int, ref: string, depth: int}>
     */
    public function impactAnalysis(int $projectId, string $type, int $id, int $maxDepth = 5): array
    {
        $visited = [];
        $queue   = [['type' => $type, 'id' => $id, 'depth' => 0]];
        $results = [];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $key     = $current['type'] . ':' . $current['id'];

            if (isset($visited[$key]) || $current['depth'] >= $maxDepth) {
                continue;
            }
            $visited[$key] = true;

            $impacted = AiAgentTraceabilityLink::impactedBy($projectId, $current['type'], $current['id']);

            foreach ($impacted as $node) {
                $nodeKey = $node['type'] . ':' . $node['id'];
                if (!isset($visited[$nodeKey])) {
                    $entry     = array_merge($node, ['depth' => $current['depth'] + 1]);
                    $results[] = $entry;
                    $queue[]   = ['type' => $node['type'], 'id' => $node['id'], 'depth' => $current['depth'] + 1];
                }
            }
        }

        return $results;
    }

    /**
     * 프로젝트 내 특정 타입의 모든 링크 삭제 (재생성 시 사용).
     */
    public function removeLinksFrom(int $projectId, string $sourceType, int $sourceId): int
    {
        return AiAgentTraceabilityLink::where('project_id', $projectId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * Requirement → Screen 표준 링크 생성 헬퍼.
     */
    public function linkRequirementToScreen(
        int    $projectId,
        int    $requirementId,
        string $reqId,
        int    $screenId,
        string $screenId2
    ): AiAgentTraceabilityLink {
        return $this->link(
            $projectId,
            'requirement', $requirementId, $reqId,
            'screen', $screenId, $screenId2,
            'designs'
        );
    }

    /**
     * Artifact → Requirement 구현 링크 생성 헬퍼.
     */
    public function linkArtifactToRequirement(
        int    $projectId,
        int    $artifactId,
        string $artifactRef,
        int    $requirementId,
        string $reqId
    ): AiAgentTraceabilityLink {
        return $this->link(
            $projectId,
            'artifact', $artifactId, $artifactRef,
            'requirement', $requirementId, $reqId,
            'implements'
        );
    }
}
