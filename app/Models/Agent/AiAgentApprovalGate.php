<?php

namespace App\Models\Agent;

use App\Enums\Agent\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentApprovalGate extends Model
{
    protected $table = 'ai_agent_approval_gates';

    protected $fillable = [
        'project_id',
        'stage_id',
        'artifact_id',
        'gate_type',
        'status',
        'requested_by',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'request_comment',
        'review_comment',
    ];

    protected $casts = [
        'status'       => ApprovalStatus::class,
        'requested_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(AiAgentProjectStage::class, 'stage_id');
    }

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::PENDING;
    }

    public function approve(int $reviewerId, ?string $comment = null): void
    {
        $this->update([
            'status'         => ApprovalStatus::APPROVED,
            'reviewed_by'    => $reviewerId,
            'reviewed_at'    => now(),
            'review_comment' => $comment,
        ]);

        // 산출물 승인 시 Artifact 상태도 동기화
        if ($this->artifact_id && $this->artifact) {
            $this->artifact->update(['status' => 'approved', 'approved_by' => $reviewerId, 'approved_at' => now()]);
        }

        // 단계 완성 게이트라면 Stage 승인 처리
        if ($this->gate_type === 'stage_completion' && $this->stage) {
            $this->stage->approve($reviewerId);
        }
    }

    public function reject(int $reviewerId, ?string $comment = null): void
    {
        $this->update([
            'status'         => ApprovalStatus::REJECTED,
            'reviewed_by'    => $reviewerId,
            'reviewed_at'    => now(),
            'review_comment' => $comment,
        ]);

        if ($this->artifact_id && $this->artifact) {
            $this->artifact->update(['status' => 'rejected']);
        }
    }

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::PENDING);
    }
}
