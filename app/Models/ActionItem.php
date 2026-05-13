<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class ActionItem extends Model
{
    use LogsActivity;
    protected $fillable = [
        'user_id', 'assigned_to', 'project_id',
        'title', 'description', 'due_date',
        'is_completed', 'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isDueSoon(): bool
    {
        return $this->due_date && $this->due_date->lte(now()->addDays(3)) && !$this->is_completed;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->lt(now()->startOfDay()) && !$this->is_completed;
    }
}
