<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromptHistory extends Model
{
    protected $primaryKey = 'history_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'history_id',
        'user_id',
        'session_id',
        'mode',
        'project_id',
        'schedule_id',
        'task_type',
        'original_input',
        'clarification_rounds',
        'refined_prompt',
        'metadata',
        'llm_model',
        'provider_used',
        'fallback_reason',
        'total_tokens',
        'elapsed_ms',
    ];

    protected $casts = [
        'clarification_rounds' => 'array',
        'metadata'             => 'array',
    ];

    public static function saveResult(
        int $userId,
        string $sessionId,
        string $mode,
        ?int $projectId,
        ?int $scheduleId,
        string $taskType,
        string $originalInput,
        array $clarificationRounds,
        string $refinedPrompt,
        array $metadata,
        string $llmModel,
        int $totalTokens,
        int $elapsedMs,
        ?string $providerUsed = null,
        ?string $fallbackReason = null,
    ): self {
        return self::create([
            'history_id'           => 'hist_' . Str::random(16),
            'user_id'              => $userId,
            'session_id'           => $sessionId,
            'mode'                 => $mode,
            'project_id'           => $projectId,
            'schedule_id'          => $scheduleId,
            'task_type'            => $taskType,
            'original_input'       => $originalInput,
            'clarification_rounds' => $clarificationRounds,
            'refined_prompt'       => $refinedPrompt,
            'metadata'             => $metadata,
            'llm_model'            => $llmModel,
            'provider_used'        => $providerUsed,
            'fallback_reason'      => $fallbackReason,
            'total_tokens'         => $totalTokens,
            'elapsed_ms'           => $elapsedMs,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
