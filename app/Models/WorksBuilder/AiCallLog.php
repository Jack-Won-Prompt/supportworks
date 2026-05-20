<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 명세 v11 §2.4 — AiCallLog.
 */
class AiCallLog extends Model
{
    protected $table = 'wb_ai_call_logs';

    public const STATUS_SUCCESS   = 'success';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'task_id', 'stage', 'review_round', 'internal_prompt_id',
        'primary_provider', 'fallback_used', 'final_provider',
        'status',
        'primary_attempt_status', 'primary_error_message',
        'fallback_attempt_status', 'fallback_error_message',
        'prompt_tokens', 'completion_tokens', 'total_tokens',
        'estimated_cost_usd', 'response_time_ms',
        'generated_html_id',
    ];

    protected $casts = [
        'fallback_used'      => 'boolean',
        'estimated_cost_usd' => 'decimal:4',
        'review_round'       => 'integer',
        'prompt_tokens'      => 'integer',
        'completion_tokens'  => 'integer',
        'total_tokens'       => 'integer',
        'response_time_ms'   => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function internalPrompt(): BelongsTo
    {
        return $this->belongsTo(InternalPrompt::class, 'internal_prompt_id');
    }

    public function generatedHtml(): BelongsTo
    {
        return $this->belongsTo(GeneratedHtml::class, 'generated_html_id');
    }
}
