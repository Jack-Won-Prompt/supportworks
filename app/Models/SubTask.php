<?php

namespace App\Models;

use App\Models\ProjectFile;
use App\Models\Requirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'task_group_id', 'title', 'description',
        'start_date', 'end_date', 'assignee_id',
        'status', 'progress', 'display_order',
        'source_type', 'source_plan_id', 'requirement_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'progress'   => 'integer',
    ];

    const STATUS_LABELS = [
        'not_started' => '미시작',
        'in_progress' => '진행중',
        'completed'   => '완료',
        'blocked'     => '블로킹',
    ];

    const STATUS_COLORS = [
        'not_started' => 'gray',
        'in_progress' => 'blue',
        'completed'   => 'green',
        'blocked'     => 'red',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function taskGroup(): BelongsTo
    {
        return $this->belongsTo(TaskGroup::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * 다중 담당자 (피벗 — 2026-05 추가). 기존 assignee 는 '대표' 단일 유지.
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sub_task_assignees', 'sub_task_id', 'user_id')
            ->withTimestamps();
    }

    public function sourcePlan(): BelongsTo
    {
        return $this->belongsTo(PlanningDoc::class, 'source_plan_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class, 'sub_task_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }
}
