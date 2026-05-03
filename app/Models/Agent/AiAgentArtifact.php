<?php

namespace App\Models\Agent;

use App\Enums\Agent\ArtifactStatus;
use App\Enums\Agent\ArtifactType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentArtifact extends Model
{
    protected $table = 'ai_agent_artifacts';

    protected $fillable = [
        'project_id',
        'stage_id',
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
}
