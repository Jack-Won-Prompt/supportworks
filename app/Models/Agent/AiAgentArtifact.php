<?php

namespace App\Models\Agent;

use App\Enums\Agent\ArtifactStatus;
use App\Enums\Agent\ArtifactType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentArtifact extends Model
{
    protected $table = 'ai_agent_artifacts';

    protected $fillable = [
        'project_id',
        'stage_id',
        'scope_type',
        'scope_id',
        'type',
        'title',
        'content',
        'meta',
        'version',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'type'        => ArtifactType::class,
        'status'      => ArtifactStatus::class,
        'meta'        => 'array',
        'approved_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function stage(): BelongsTo
    {
        return $this->belongsTo(AiAgentProjectStage::class, 'stage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AiAgentArtifactVersion::class, 'artifact_id')->orderByDesc('version');
    }

    public function approvalGates(): HasMany
    {
        return $this->hasMany(AiAgentApprovalGate::class, 'artifact_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AiAgentArtifactFile::class, 'artifact_id')->orderBy('created_at');
    }

    // ── Scope helpers ────────────────────────────────────────────────────────

    public function isProjectScope(): bool
    {
        return $this->scope_type === 'project';
    }

    public function isScreenScope(): bool
    {
        return $this->scope_type === 'screen';
    }

    /**
     * 스코프 대상 모델 반환 (Project 또는 AiAgentScreen)
     */
    public function getScopeTarget(): Project|AiAgentScreen|null
    {
        if ($this->scope_type === 'screen') {
            return AiAgentScreen::find($this->scope_id);
        }

        return Project::find($this->scope_id);
    }

    /**
     * 스코프 레이블 (목록 표시용)
     */
    public function getScopeLabelAttribute(): string
    {
        if ($this->scope_type === 'screen') {
            $screen = AiAgentScreen::where('id', $this->scope_id)->value('screen_id');
            return $screen ?? "SCR-?";
        }

        return '프로젝트 전체';
    }

    // ── Query scopes ─────────────────────────────────────────────────────────

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('scope_type', 'project')->where('scope_id', $projectId);
    }

    public function scopeForScreen(Builder $query, int $screenId): Builder
    {
        return $query->where('scope_type', 'screen')->where('scope_id', $screenId);
    }

    public function scopeOfScopeType(Builder $query, string $scopeType): Builder
    {
        return $query->where('scope_type', $scopeType);
    }

    public function scopeOfType(Builder $query, ArtifactType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    // ── Versioning ───────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    // 현재 내용을 버전 이력에 저장 후 새 내용으로 업데이트
    public function updateWithVersion(string $content, int $userId, ?string $changeSummary = null, ?array $meta = null): void
    {
        AiAgentArtifactVersion::create([
            'artifact_id'    => $this->id,
            'version'        => $this->version,
            'content'        => $this->content,
            'meta'           => $this->meta,
            'change_summary' => $changeSummary,
            'created_by'     => $userId,
        ]);

        $this->update([
            'content' => $content,
            'meta'    => $meta ?? $this->meta,
            'version' => $this->version + 1,
            'status'  => ArtifactStatus::DRAFT,
        ]);
    }

    /**
     * 동일 scope+type 산출물이 있으면 갱신, 없으면 생성 (T17+ AS-IS 저장용)
     */
    public static function upsertForScope(
        int $projectId,
        int $stageId,
        ArtifactType $type,
        string $scopeType,
        int $scopeId,
        string $title,
        string $content,
        int $userId,
        ?array $meta = null,
    ): static {
        $existing = static::where('project_id', $projectId)
            ->where('type', $type->value)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->first();

        if ($existing) {
            $existing->updateWithVersion($content, $userId, null, $meta);
            return $existing->fresh();
        }

        return static::create([
            'project_id' => $projectId,
            'stage_id'   => $stageId,
            'scope_type' => $scopeType,
            'scope_id'   => $scopeId,
            'type'       => $type->value,
            'title'      => $title,
            'content'    => $content,
            'meta'       => $meta,
            'version'    => 1,
            'status'     => ArtifactStatus::DRAFT->value,
            'created_by' => $userId,
        ]);
    }
}
