<?php

namespace App\Models\Agent;

use App\Enums\Agent\RequirementPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AiAgentRequirement extends Model
{
    protected $table = 'ai_agent_requirements';

    protected $fillable = [
        'project_id',
        'artifact_id',
        'req_id',
        'title',
        'description',
        'rationale',
        'source_files',
        'priority',
        'category',
        'source',
        'status',
    ];

    protected $casts = [
        'priority'     => RequirementPriority::class,
        'source_files' => 'array',
    ];

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function traceabilityLinks(): HasMany
    {
        return $this->hasMany(AiAgentTraceabilityLink::class, 'source_id')
            ->where('source_type', 'requirement');
    }

    // REQ-001 형식으로 다음 순번 자동 생성 (concurrent-safe)
    public static function nextReqId(int $projectId): string
    {
        return DB::transaction(function () use ($projectId) {
            $max = static::where('project_id', $projectId)
                ->lockForUpdate()
                ->orderByDesc('req_id')
                ->value('req_id');

            if (!$max) {
                return 'REQ-001';
            }

            $num = (int) substr($max, 4) + 1;
            return 'REQ-' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });
    }

    public function scopeByPriority($query, RequirementPriority $priority)
    {
        return $query->where('priority', $priority);
    }
}
