<?php

namespace App\Models\WorksBuilder;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * 명세 v11 §2.2 — Task.
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wb_tasks';

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_AI_CALLING  = 'ai_calling';
    public const STATUS_REVIEW      = 'review';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    protected $fillable = [
        'task_uuid', 'project_id',
        'spec_reference_type', 'spec_reference_id',
        'mode',
        'parent_task_id', 'reopen_reason',
        'assignee_id',
        'current_stage', 'status',
        'current_review_round', 'output_type',
        'total_ai_calls', 'total_tokens_used', 'total_cost_usd',
        'started_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
        'cancelled_at'         => 'datetime',
        'current_review_round' => 'integer',
        'total_ai_calls'       => 'integer',
        'total_tokens_used'    => 'integer',
        'total_cost_usd'       => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $task) {
            if (empty($task->task_uuid)) {
                $task->task_uuid = (string) Str::uuid();
            }
        });
    }

    public function isCompleted(): bool   { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool   { return $this->status === self::STATUS_CANCELLED; }
    public function isImmutable(): bool   { return $this->isCompleted() || $this->isCancelled(); }
    public function isAiCalling(): bool   { return $this->status === self::STATUS_AI_CALLING; }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(TaskOption::class, 'task_id');
    }

    public function currentOption(): HasOne
    {
        return $this->hasOne(TaskOption::class, 'task_id')->where('is_current', true);
    }

    public function layoutPreviews(): HasMany
    {
        return $this->hasMany(LayoutPreview::class, 'task_id');
    }

    public function internalPrompts(): HasMany
    {
        return $this->hasMany(InternalPrompt::class, 'task_id');
    }

    public function aiCallLogs(): HasMany
    {
        return $this->hasMany(AiCallLog::class, 'task_id');
    }

    public function generatedHtml(): HasMany
    {
        return $this->hasMany(GeneratedHtml::class, 'task_id');
    }

    public function resultConfirmations(): HasMany
    {
        return $this->hasMany(ResultConfirmation::class, 'task_id');
    }

    public function reviewSessions(): HasMany
    {
        return $this->hasMany(ReviewSession::class, 'task_id');
    }

    public function ngInputs(): HasMany
    {
        return $this->hasMany(NgInput::class, 'task_id');
    }

    public function outputPackages(): HasMany
    {
        return $this->hasMany(OutputPackage::class, 'task_id');
    }
}
