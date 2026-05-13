<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use LogsActivity;
    protected $fillable = [
        'user_id', 'project_id',
        'title', 'description',
        'status', 'priority', 'due_date',
    ];

    protected $casts = ['due_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'todo'        => '할 일',
            'in_progress' => '진행 중',
            'done'        => '완료',
            default       => $this->status,
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'high'   => '높음',
            'medium' => '보통',
            'low'    => '낮음',
            default  => $this->priority,
        };
    }

    public function getNextStatusAttribute(): ?string
    {
        return match($this->status) {
            'todo'        => 'in_progress',
            'in_progress' => 'done',
            default       => null,
        };
    }

    public function getPrevStatusAttribute(): ?string
    {
        return match($this->status) {
            'in_progress' => 'todo',
            'done'        => 'in_progress',
            default       => null,
        };
    }
}
