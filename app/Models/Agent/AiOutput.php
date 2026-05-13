<?php

namespace App\Models\Agent;

use App\Enums\Agent\AgentOutputStatus;
use App\Enums\Agent\FrontendStack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiOutput extends Model
{
    protected $table = 'ai_outputs';

    protected $fillable = [
        'session_id',
        'analysis_step_id',
        'version_no',
        'output_type',
        'files_json',
        'zip_path',
        'preview_url',
        'status',
        'generated_by',
        'model_used',
        'input_tokens',
        'output_tokens',
        'change_summary',
        'generated_at',
    ];

    protected $casts = [
        'output_type'  => FrontendStack::class,
        'status'       => AgentOutputStatus::class,
        'generated_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    public function analysisStep(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisStep::class, 'analysis_step_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(AiFeedback::class, 'output_id')->orderByDesc('created_at');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(AiConflict::class, 'output_id');
    }

    public function confirmedOutput(): HasOne
    {
        return $this->hasOne(AiConfirmedOutput::class, 'output_id');
    }

    public function scopeOfSession(Builder $q, int $sessionId): Builder
    {
        return $q->where('session_id', $sessionId);
    }

    /**
     * files_json은 longText로 저장되며 JSON 배열을 직렬화.
     * accessor/mutator로 PHP 배열 ↔ JSON 변환.
     *
     * @return array<int, array{path: string, type: string, content: string, summary?: string}>
     */
    public function getFilesAttribute(): array
    {
        if (empty($this->files_json)) {
            return [];
        }

        $decoded = json_decode($this->files_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setFilesAttribute(array $files): void
    {
        $this->attributes['files_json'] = json_encode(array_values($files), JSON_UNESCAPED_UNICODE);
    }

    public function isConfirmed(): bool
    {
        return $this->status === AgentOutputStatus::CONFIRMED;
    }
}
