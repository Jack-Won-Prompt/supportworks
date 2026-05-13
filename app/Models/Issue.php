<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Issue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'title', 'description', 'category', 'status', 'priority',
        'severity', 'environment', 'reporter_id', 'assignee_id', 'tags',
        'resolution', 'resolved_at', 'resolved_by_id',
        'sla_due', 'sla_breached',
        'linked_requirement_id', 'converted_from_question_id',
    ];

    protected $casts = [
        'tags'         => 'array',
        'sla_breached' => 'boolean',
        'resolved_at'  => 'datetime',
        'sla_due'      => 'datetime',
    ];

    public const STATUS_LABELS = [
        '신규'  => '신규',
        '처리중' => '처리중',
        '해결'  => '해결',
        '검증중' => '검증중',
        '종결'  => '종결',
        '보류'  => '보류',
        '반려'  => '반려',
    ];

    public const STATUS_COLORS = [
        '신규'  => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
        '처리중' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        '해결'  => ['bg' => '#d1fae5', 'text' => '#065f46'],
        '검증중' => ['bg' => '#ede9fe', 'text' => '#5b21b6'],
        '종결'  => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
        '보류'  => ['bg' => '#fce7f3', 'text' => '#9d174d'],
        '반려'  => ['bg' => '#fee2e2', 'text' => '#991b1b'],
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
        '버그'   => '버그',
        '장애'   => '장애',
        '문의'   => '문의',
        '개선요청' => '개선요청',
        '기타'   => '기타',
    ];

    public const SEVERITY_LABELS = [
        'Critical' => 'Critical',
        'Major'    => 'Major',
        'Minor'    => 'Minor',
        'Trivial'  => 'Trivial',
    ];

    public const SEVERITY_COLORS = [
        'Critical' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'Major'    => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'Minor'    => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
        'Trivial'  => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
    ];

    public const ENVIRONMENT_LABELS = [
        '운영'   => '운영',
        '스테이징' => '스테이징',
        '개발'   => '개발',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function linkedRequirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class, 'linked_requirement_id');
    }

    public function convertedFromQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'converted_from_question_id');
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

    public function getSeverityColorAttribute(): array
    {
        return self::SEVERITY_COLORS[$this->severity] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
    }

    public function isWatchedBy(int $userId): bool
    {
        return $this->watchers()->where('user_id', $userId)->exists();
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['해결', '종결'], true);
    }
}
