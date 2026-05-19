<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDoAct extends Model
{
    protected $table = 'plan_do_acts';

    protected $fillable = [
        'project_id',
        'user_id',
        'source_file_comment_id',
        'source_message_id',
        'title',
        'plan',
        'do',
        'act',
        'status',
        'source_excerpt',
    ];

    public const STATUSES = ['plan', 'do', 'act', 'done'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sourceFileComment(): BelongsTo
    {
        return $this->belongsTo(FileComment::class, 'source_file_comment_id');
    }

    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'source_message_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return in_array($this->status, self::STATUSES, true)
            ? __('plan-do-acts.status_' . $this->status)
            : $this->status;
    }

    /** 상태 배지 색상 {bg, fg} */
    public function statusColors(): array
    {
        return [
            'plan' => ['bg' => '#dbeafe', 'fg' => '#2563eb'],
            'do'   => ['bg' => '#fef3c7', 'fg' => '#b45309'],
            'act'  => ['bg' => '#ede9fe', 'fg' => '#7c3aed'],
            'done' => ['bg' => '#dcfce7', 'fg' => '#16a34a'],
        ][$this->status] ?? ['bg' => '#f3f4f6', 'fg' => '#6b7280'];
    }
}
