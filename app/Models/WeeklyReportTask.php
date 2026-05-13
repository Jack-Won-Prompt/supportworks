<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyReportTask extends Model
{
    protected $fillable = [
        'weekly_report_id', 'section', 'task_name',
        'start_date', 'end_date', 'status',
        'original_data', 'sort_order',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'original_data' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(WeeklyReport::class, 'weekly_report_id');
    }

    public function getIsModifiedAttribute(): bool
    {
        if (!$this->original_data) return false;
        $orig = $this->original_data;
        return $this->task_name !== ($orig['task_name'] ?? null)
            || ($this->start_date?->format('Y-m-d')) !== ($orig['start_date'] ?? null)
            || ($this->end_date?->format('Y-m-d')) !== ($orig['end_date'] ?? null);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed'  => '완료',
            'in_progress' => '진행중',
            'pending'    => '미착수',
            'planned'    => '계획',
            default      => $this->status,
        };
    }
}
