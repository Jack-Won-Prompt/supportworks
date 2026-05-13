<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnalysisStep extends Model
{
    protected $table = 'ai_analysis_steps';

    public const STATUS_PENDING              = 'pending';
    public const STATUS_IN_PROGRESS          = 'in_progress';
    public const STATUS_DONE                 = 'done';
    public const STATUS_USER_INPUT_REQUIRED  = 'user_input_required';
    public const STATUS_FAILED               = 'failed';
    public const STATUS_SKIPPED              = 'skipped';

    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';
    public const DECISION_REVISE   = 'revise';

    protected $fillable = [
        'session_id',
        'step_key',
        'title',
        'description',
        'status',
        'sort_order',
        'requires_user_decision',
        'user_decision',
        'meta',
        'failure_reason',
    ];

    protected $casts = [
        'requires_user_decision' => 'boolean',
        'meta'                   => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(AiOutput::class, 'analysis_step_id');
    }

    public function scopeWaitingDecision(Builder $q): Builder
    {
        return $q->where('requires_user_decision', true)
            ->whereNull('user_decision');
    }
}
