<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class MeetingActionItem extends Model
{
    use LogsActivity;
    protected $fillable = [
        'minute_id', 'title', 'description',
        'owner_id', 'owner_name', 'due_date',
        'priority', 'status', 'memo_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function minute()
    {
        return $this->belongsTo(MeetingMinute::class, 'minute_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memo()
    {
        return $this->belongsTo(MeetingMemo::class, 'memo_id');
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'high'   => '높음',
            'low'    => '낮음',
            default  => '보통',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high'  => '#ef4444',
            'low'   => '#22c55e',
            default => '#f59e0b',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'in_progress' => '진행중',
            'completed'   => '완료',
            default       => '대기',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'in_progress' => '#3b82f6',
            'completed'   => '#22c55e',
            default       => '#94a3b8',
        };
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->status !== 'completed'
            && $this->due_date->isPast();
    }

    public function getOwnerDisplayAttribute(): string
    {
        return $this->owner?->name ?? $this->owner_name ?? '미지정';
    }
}
