<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentTraceabilityLink extends Model
{
    protected $table = 'ai_agent_traceability_links';

    public const SOURCE_TYPES = ['requirement', 'screen', 'component', 'api_endpoint', 'code_file', 'artifact'];
    public const LINK_TYPES   = ['implements', 'designs', 'tests', 'documents', 'depends_on'];

    protected $fillable = [
        'project_id',
        'source_type',
        'source_id',
        'source_ref',
        'target_type',
        'target_id',
        'target_ref',
        'link_type',
    ];

    public static function createLink(
        int    $projectId,
        string $sourceType,
        int    $sourceId,
        string $sourceRef,
        string $targetType,
        int    $targetId,
        string $targetRef,
        string $linkType = 'implements'
    ): self {
        return static::create(compact(
            'projectId', 'sourceType', 'sourceId', 'sourceRef',
            'targetType', 'targetId', 'targetRef', 'linkType'
        ));
    }

    // 특정 소스의 모든 연결 대상 조회
    public function scopeFromSource($query, string $type, int $id)
    {
        return $query->where('source_type', $type)->where('source_id', $id);
    }

    // 특정 대상을 참조하는 모든 소스 조회
    public function scopeToTarget($query, string $type, int $id)
    {
        return $query->where('target_type', $type)->where('target_id', $id);
    }

    // 영향 분석: 해당 노드를 참조하는 상위 체인 수집
    public static function impactedBy(int $projectId, string $type, int $id): array
    {
        return static::where('project_id', $projectId)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->get()
            ->map(fn($l) => ['type' => $l->source_type, 'id' => $l->source_id, 'ref' => $l->source_ref])
            ->toArray();
    }
}
