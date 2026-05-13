<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use LogsActivity;

    protected $table = 'legacy_schedules';

    protected $fillable = [
        'project_id', 'title', 'group_name', 'sort_order', 'description', 'start_date', 'end_date',
        'status', 'priority', 'assigned_to', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'          => 'yellow',
            'in_progress'      => 'blue',
            'completed'        => 'green',
            'cancelled'        => 'red',
            'review_submitted' => 'orange',
            'review_completed' => 'purple',
            default            => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'          => '대기',
            'in_progress'      => '진행중',
            'completed'        => '완료',
            'cancelled'        => '취소',
            'review_submitted' => '검토제출',
            'review_completed' => '검토완료',
            default            => '알 수 없음',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'high' => '높음',
            'medium' => '중간',
            'low' => '낮음',
            default => '중간',
        };
    }
}
