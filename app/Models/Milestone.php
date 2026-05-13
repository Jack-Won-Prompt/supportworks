<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'title', 'description', 'target_date', 'status', 'display_order',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    const STATUS_LABELS = [
        'planned'    => '계획',
        'in_progress'=> '진행중',
        'completed'  => '완료',
        'cancelled'  => '취소',
    ];

    const STATUS_COLORS = [
        'planned'    => 'gray',
        'in_progress'=> 'blue',
        'completed'  => 'green',
        'cancelled'  => 'red',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function taskGroups(): HasMany
    {
        return $this->hasMany(TaskGroup::class)->orderBy('display_order');
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
