<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'created_by_id', 'status',
        'input_text', 'llm_provider', 'llm_model', 'system_prompt_version',
        'ai_raw_output', 'ai_structured_output',
        'token_input', 'token_output', 'cost_estimated',
        'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'ai_raw_output'        => 'array',
        'ai_structured_output' => 'array',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
        'cost_estimated'       => 'decimal:4',
    ];

    public const STATUS_LABELS = [
        'pending'    => '대기중',
        'processing' => '분석중',
        'review'     => '검토 필요',
        'approved'   => '등록완료',
        'rejected'   => '거부됨',
        'failed'     => '실패',
    ];

    public const PROVIDER_MODELS = [
        'anthropic' => ['claude-opus-4-7', 'claude-sonnet-4-6', 'claude-haiku-4-5-20251001'],
        'openai'    => ['gpt-4o', 'gpt-4-turbo', 'gpt-3.5-turbo'],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AnalysisSessionFile::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class, 'source_session_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
    public function isReview(): bool     { return $this->status === 'review'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }

    public function getCandidatesAttribute(): array
    {
        return $this->ai_structured_output['requirements'] ?? [];
    }

    public function getSummaryAttribute(): ?string
    {
        return $this->ai_structured_output['summary'] ?? null;
    }

    public function getWarningsAttribute(): array
    {
        return $this->ai_structured_output['warnings'] ?? [];
    }
}
