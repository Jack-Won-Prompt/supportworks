<?php

namespace App\Models\WorksBuilder;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'wb_notifications';

    protected $fillable = [
        'recipient_id', 'task_id', 'project_id',
        'stage_code', 'review_round',
        'title', 'message', 'deep_link',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read'      => 'boolean',
        'read_at'      => 'datetime',
        'review_round' => 'integer',
    ];

    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }
    public function task(): BelongsTo      { return $this->belongsTo(Task::class, 'task_id'); }
    public function project(): BelongsTo   { return $this->belongsTo(Project::class); }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('is_read', false);
    }
}
