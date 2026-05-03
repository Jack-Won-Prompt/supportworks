<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentUsageLog extends Model
{
    protected $table = 'ai_agent_usage_logs';

    protected $fillable = [
        'project_id',
        'user_id',
        'artifact_id',
        'stage',
        'task_type',
        'model',
        'provider',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'duration_ms',
        'status',
        'error_message',
    ];

    protected $casts = [
        'input_tokens'  => 'integer',
        'output_tokens' => 'integer',
        'cost_usd'      => 'decimal:6',
        'duration_ms'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // 총 비용 집계
    public static function totalCostForProject(int $projectId): float
    {
        return (float) static::forProject($projectId)->sum('cost_usd');
    }

    public static function record(
        int     $userId,
        string  $model,
        string  $provider,
        int     $inputTokens,
        int     $outputTokens,
        float   $costUsd,
        ?int    $projectId = null,
        ?int    $artifactId = null,
        ?string $stage = null,
        ?string $taskType = null,
        ?int    $durationMs = null,
        string  $status = 'success',
        ?string $errorMessage = null
    ): self {
        return static::create([
            'project_id'    => $projectId,
            'user_id'       => $userId,
            'artifact_id'   => $artifactId,
            'stage'         => $stage,
            'task_type'     => $taskType,
            'model'         => $model,
            'provider'      => $provider,
            'input_tokens'  => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd'      => $costUsd,
            'duration_ms'   => $durationMs,
            'status'        => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
