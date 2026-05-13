<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLeave extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'start_date', 'end_date',
        'leave_type', 'reason', 'status', 'created_by', 'approver_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match ($this->leave_type) {
            'annual'       => '연차',
            'half_day_am'  => '오전 반차',
            'half_day_pm'  => '오후 반차',
            'sick'         => '병가',
            default        => '기타',
        };
    }

    public function getLeaveTypeColorAttribute(): string
    {
        return match ($this->leave_type) {
            'annual'       => '#6366f1',
            'half_day_am'  => '#0ea5e9',
            'half_day_pm'  => '#8b5cf6',
            'sick'         => '#f59e0b',
            default        => '#64748b',
        };
    }

    public function getLeaveTypeBgAttribute(): string
    {
        return match ($this->leave_type) {
            'annual'       => '#eef2ff',
            'half_day_am'  => '#e0f2fe',
            'half_day_pm'  => '#f3e8ff',
            'sick'         => '#fef3c7',
            default        => '#f1f5f9',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => '승인',
            'rejected' => '반려',
            default    => '대기',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => '#059669',
            'rejected' => '#dc2626',
            default    => '#d97706',
        };
    }

    public function getStatusBgAttribute(): string
    {
        return match ($this->status) {
            'approved' => '#d1fae5',
            'rejected' => '#fee2e2',
            default    => '#fef3c7',
        };
    }

    public function getDaysCountAttribute(): int
    {
        if (in_array($this->leave_type, ['half_day_am', 'half_day_pm'])) {
            return 0; // 0.5일이지만 표시용
        }
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
