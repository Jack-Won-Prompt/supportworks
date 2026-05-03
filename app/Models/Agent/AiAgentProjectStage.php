<?php

namespace App\Models\Agent;

use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentProjectStage extends Model
{
    protected $table = 'ai_agent_project_stages';

    protected $fillable = [
        'project_id',
        'type',
        'name',
        'status',
        'order',
        'started_at',
        'completed_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'type'         => StageType::class,
        'status'       => StageStatus::class,
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(ProjectAiAgentConfig::class, 'project_id', 'project_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(AiAgentArtifact::class, 'stage_id');
    }

    public function approvalGates(): HasMany
    {
        return $this->hasMany(AiAgentApprovalGate::class, 'stage_id');
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isLocked(): bool
    {
        return $this->status === StageStatus::LOCKED;
    }

    public function unlock(): void
    {
        if ($this->status === StageStatus::LOCKED) {
            $this->update([
                'status'     => StageStatus::IN_PROGRESS,
                'started_at' => now(),
            ]);
        }
    }

    public function requestApproval(): void
    {
        $this->update([
            'status'       => StageStatus::PENDING_APPROVAL,
            'completed_at' => now(),
        ]);
    }

    public function approve(int $approvedBy): void
    {
        $this->update([
            'status'      => StageStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);
    }
}
