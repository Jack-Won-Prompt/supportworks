<?php

namespace App\Models\WorksBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Task 처리 step-by-step audit 로그.
 * GenerateHtmlJob / RegenerateHtmlJob / ReopenHtmlJob 각 단계마다 1 row.
 */
class TaskStep extends Model
{
    protected $table = 'wb_task_steps';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'task_id', 'ai_call_log_id', 'sequence', 'code', 'label',
        'status', 'context', 'started_at', 'ended_at', 'duration_ms',
    ];

    protected $casts = [
        'context'     => 'array',
        'started_at'  => 'datetime',
        'ended_at'    => 'datetime',
        'sequence'    => 'integer',
        'duration_ms' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function aiCallLog(): BelongsTo
    {
        return $this->belongsTo(AiCallLog::class, 'ai_call_log_id');
    }
}
