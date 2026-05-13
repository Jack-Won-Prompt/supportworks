<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Requirement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'title', 'description', 'status', 'priority', 'category',
        'assignee_id', 'reporter_id', 'tags',
        'requirement_type', 'source_ref', 'approval_status',
        'approved_by_id', 'approved_at',
        'source_type', 'source_session_id', 'ai_confidence',
        'applied_to_plan', 'applied_to_plan_at', 'applied_to_plan_id',
    ];

    protected $casts = [
        'applied_to_plan'   => 'boolean',
        'approved_at'       => 'datetime',
        'applied_to_plan_at'=> 'datetime',
    ];

    public function getTagsAttribute(?string $value): ?array
    {
        if (empty($value)) return null;
        $decoded = json_decode($value, true);
        if (is_array($decoded)) return $decoded;
        // JSON 디코딩 결과가 문자열인 경우 (단일 값이 JSON string으로 저장된 레거시)
        if (is_string($decoded)) {
            return array_values(array_filter(array_map('trim', explode(',', $decoded))));
        }
        // 레거시 콤마 구분 원문 문자열 처리
        $parts = array_values(array_filter(array_map('trim', explode(',', $value))));
        return $parts ?: null;
    }

    public function setTagsAttribute(mixed $value): void
    {
        if (empty($value)) {
            $this->attributes['tags'] = null;
        } elseif (is_array($value)) {
            $this->attributes['tags'] = json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        } else {
            $this->attributes['tags'] = $value;
        }
    }

    public const STATUS_LABELS = [
        'draft'     => '초안',
        'analyzing' => '분석중',
        'confirmed' => '확정',
        'changed'   => '변경',
        'deferred'  => '보류',
        'cancelled' => '취소',
    ];

    public const STATUS_COLORS = [
        'draft'     => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
        'analyzing' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
        'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
        'changed'   => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'deferred'  => ['bg' => '#f3e8ff', 'text' => '#6b21a8'],
        'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    ];

    public const PRIORITY_LABELS = [
        'critical' => 'Critical',
        'high'     => 'High',
        'medium'   => 'Medium',
        'low'      => 'Low',
    ];

    public const PRIORITY_COLORS = [
        'critical' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'high'     => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'medium'   => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
        'low'      => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
    ];

    public const CATEGORY_LABELS = [
        'functional'     => '기능',
        'non_functional' => '비기능',
        'ui'             => 'UI',
        'data'           => '데이터',
        'security'       => '보안',
    ];

    public const TYPE_LABELS = [
        'initial'    => '초기확정',
        'additional' => '추가요청',
        'change'     => '변경요청',
    ];

    public const APPROVAL_LABELS = [
        'reviewing' => '검토중',
        'approved'  => '고객승인',
        'rejected'  => '반려',
        'returned'  => '반려',
    ];

    public const APPROVAL_COLORS = [
        'reviewing' => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
        'approved'  => ['bg' => '#d1fae5', 'text' => '#065f46'],
        'rejected'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'returned'  => ['bg' => '#fef3c7', 'text' => '#92400e'],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ItemComment::class, 'item')->latest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(ItemAttachment::class, 'item');
    }

    public function watchers(): MorphMany
    {
        return $this->morphMany(ItemWatcher::class, 'item');
    }

    public function changeHistories(): MorphMany
    {
        return $this->morphMany(ItemChangeHistory::class, 'item')->latest('changed_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): array
    {
        return self::STATUS_COLORS[$this->status] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): array
    {
        return self::PRIORITY_COLORS[$this->priority] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->requirement_type] ?? $this->requirement_type;
    }

    public function getApprovalLabelAttribute(): string
    {
        return self::APPROVAL_LABELS[$this->approval_status] ?? $this->approval_status;
    }

    public function getApprovalColorAttribute(): array
    {
        return self::APPROVAL_COLORS[$this->approval_status] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
    }

    public function planApplications()
    {
        return $this->hasMany(\App\Models\PlanApplication::class)->whereNull('deleted_at')->orderByDesc('applied_at');
    }

    public function appliedPlan()
    {
        return $this->belongsTo(PlanningDoc::class, 'applied_to_plan_id');
    }

    public function linkedIssues()
    {
        return $this->hasMany(Issue::class, 'linked_requirement_id');
    }

    public function isWatchedBy(int $userId): bool
    {
        return $this->watchers()->where('user_id', $userId)->exists();
    }
}
