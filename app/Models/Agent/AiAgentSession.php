<?php

namespace App\Models\Agent;

use App\Enums\Agent\AgentSessionStatus;
use App\Enums\Agent\AgentSessionStep;
use App\Enums\Agent\FrontendStack;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiAgentSession extends Model
{
    protected $table = 'ai_agent_sessions';

    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'output_type',
        'status',
        'current_step',
        'ai_provider',
        'last_activity_at',
        'paused_at',
        'failure_reason',
    ];

    protected $casts = [
        'output_type'      => FrontendStack::class,
        'status'           => AgentSessionStatus::class,
        'current_step'     => AgentSessionStep::class,
        'last_activity_at' => 'datetime',
        'paused_at'        => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function figmaSources(): HasMany
    {
        return $this->hasMany(AiFigmaSource::class, 'session_id');
    }

    public function activeFigmaSource(): HasOne
    {
        return $this->hasOne(AiFigmaSource::class, 'session_id')
            ->where('status', 'connected')
            ->latestOfMany();
    }

    public function analysisSteps(): HasMany
    {
        return $this->hasMany(AiAnalysisStep::class, 'session_id')->orderBy('sort_order');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(AiOutput::class, 'session_id')->orderByDesc('version_no');
    }

    public function latestOutput(): HasOne
    {
        return $this->hasOne(AiOutput::class, 'session_id')->latestOfMany('version_no');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(AiConflict::class, 'session_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForProject(Builder $q, int $projectId): Builder
    {
        return $q->where('project_id', $projectId);
    }

    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', [
            AgentSessionStatus::CONFIRMED->value,
            AgentSessionStatus::FAILED->value,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->save();
    }

    public function isEditable(): bool
    {
        return !$this->status->isTerminal();
    }
}
