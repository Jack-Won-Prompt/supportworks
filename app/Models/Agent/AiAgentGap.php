<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AiAgentGap extends Model
{
    use SoftDeletes;

    protected $table = 'ai_agent_gaps';

    protected $fillable = [
        'gap_id',
        'project_id',
        'artifact_id',
        'title',
        'current_state',
        'target_state',
        'category',
        'severity',
        'estimated_effort',
        'recommended_actions',
        'related_requirement_ids',
        'source',
        'created_by',
    ];

    protected $casts = [
        'recommended_actions'     => 'array',
        'related_requirement_ids' => 'array',
    ];

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Project::class, 'project_id');
    }

    // GAP-001 형식으로 다음 순번 자동 생성 (concurrent-safe)
    public static function nextGapId(int $projectId): string
    {
        return DB::transaction(function () use ($projectId) {
            $max = static::where('project_id', $projectId)
                ->lockForUpdate()
                ->orderByDesc('gap_id')
                ->value('gap_id');

            if (!$max) {
                return 'GAP-001';
            }

            $num = (int) substr($max, 4) + 1;
            return 'GAP-' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });
    }

    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
