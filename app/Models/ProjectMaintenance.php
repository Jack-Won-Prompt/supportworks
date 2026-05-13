<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMaintenance extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'title', 'content', 'priority', 'status',
        'requested_date', 'due_date', 'scheduled_date',
    ];

    protected $casts = [
        'requested_date'  => 'date',
        'due_date'        => 'date',
        'scheduled_date'  => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(ProjectMaintenanceReply::class);
    }

    public function files()
    {
        return $this->hasMany(MaintenanceFile::class, 'maintenance_id')->with('uploader')->withCount('comments')->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'     => '접수',
            'in_progress' => '처리중',
            'completed'   => '완료',
            'rejected'    => '반려',
            default       => '알 수 없음',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'     => '#d97706',
            'in_progress' => '#2563eb',
            'completed'   => '#16a34a',
            'rejected'    => '#dc2626',
            default       => '#6b7280',
        };
    }

    public function getStatusBgAttribute(): string
    {
        return match ($this->status) {
            'pending'     => '#fef3c7',
            'in_progress' => '#dbeafe',
            'completed'   => '#dcfce7',
            'rejected'    => '#fee2e2',
            default       => '#f3f4f6',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low'    => '낮음',
            'normal' => '보통',
            'high'   => '높음',
            'urgent' => '긴급',
            default  => '보통',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low'    => '#6b7280',
            'normal' => '#2563eb',
            'high'   => '#d97706',
            'urgent' => '#dc2626',
            default  => '#2563eb',
        };
    }
}
