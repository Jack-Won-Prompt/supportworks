<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'company_group_id',
        'team_name', 'author_name', 'manager_name', 'report_date',
        'year', 'week_number', 'week_start_date',
        'status', 'summary', 'special_notes',
    ];

    protected $casts = [
        'report_date'     => 'date',
        'week_start_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(WeeklyReportTask::class)->orderBy('sort_order');
    }

    public function currentTasks()
    {
        return $this->tasks()->where('section', 'current_week');
    }

    public function nextWeekTasks()
    {
        return $this->tasks()->where('section', 'next_week');
    }

    public function getWeekOfMonthAttribute(): int
    {
        return (int) ceil($this->week_start_date->day / 7);
    }

    public function getDisplayMonthAttribute(): int
    {
        return $this->week_start_date->month;
    }

    public function getWeekLabelAttribute(): string
    {
        return sprintf(
            '%d년 %d월 %d주차',
            $this->week_start_date->year,
            $this->display_month,
            $this->week_of_month
        );
    }

    public static function getWeekStartDate(Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek(Carbon::MONDAY);
    }

    public function scopeCompanyOf(Builder $query, User $user): Builder
    {
        if ($user->company_group_id) {
            return $query->where('company_group_id', $user->company_group_id);
        }
        return $query->where('user_id', $user->id)->whereNull('company_group_id');
    }
}
