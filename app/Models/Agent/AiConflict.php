<?php

namespace App\Models\Agent;

use App\Enums\Agent\AgentConflictSeverity;
use App\Enums\Agent\AgentConflictType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConflict extends Model
{
    protected $table = 'ai_conflicts';

    public const STATUS_OPEN      = 'open';
    public const STATUS_RESOLVED  = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'session_id',
        'output_id',
        'conflict_type',
        'severity',
        'description',
        'suggested_options_json',
        'user_decision',
        'user_decision_note',
        'status',
    ];

    protected $casts = [
        'conflict_type'          => AgentConflictType::class,
        'severity'               => AgentConflictSeverity::class,
        'suggested_options_json' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    public function output(): BelongsTo
    {
        return $this->belongsTo(AiOutput::class, 'output_id');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_OPEN);
    }

    /** 사용자 결정이 필요한 미해결 충돌이 있는지. */
    public function isBlocking(): bool
    {
        return $this->status === self::STATUS_OPEN && $this->severity->blocksConfirmation();
    }
}
