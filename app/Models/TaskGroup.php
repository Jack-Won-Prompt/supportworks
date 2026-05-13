<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'milestone_id', 'title', 'description', 'display_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(SubTask::class)->orderBy('display_order');
    }

    public function getProgressAttribute(): int
    {
        $tasks = $this->subTasks;
        if ($tasks->isEmpty()) return 0;
        return (int) $tasks->avg('progress');
    }
}
