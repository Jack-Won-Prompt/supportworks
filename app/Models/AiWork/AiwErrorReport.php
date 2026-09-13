<?php

namespace App\Models\AiWork;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 운영에서 올라온 에러 한 종류. 같은 지문은 이 행 하나에 쌓인다.
 */
class AiwErrorReport extends Model
{
    protected $table = 'aiw_error_reports';

    public const STATUS_NEW      = 'new';
    public const STATUS_IGNORED  = 'ignored';
    public const STATUS_QUEUED   = 'queued';
    public const STATUS_PATCHING = 'patching';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_BLOCKED  = 'blocked';

    protected $fillable = [
        'project_id', 'source_id', 'fingerprint', 'level', 'exception', 'message',
        'file', 'line', 'url', 'trace', 'context', 'count',
        'first_seen_at', 'last_seen_at', 'status', 'verdict', 'verdict_reason',
        'job_id', 'patch_attempts',
    ];

    protected $casts = [
        'context'        => 'array',
        'line'           => 'integer',
        'count'          => 'integer',
        'patch_attempts' => 'integer',
        'first_seen_at'  => 'datetime',
        'last_seen_at'   => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AiwErrorSource::class, 'source_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    /**
     * 지금 이 에러를 두고 무언가 돌고 있는가.
     *
     * 돌고 있는데 또 지시를 만들면, 같은 파일을 두 작업이 동시에 고치려 든다.
     */
    public function isBusy(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PATCHING], true);
    }

    /**
     * 자동으로 더 손대도 되는가.
     *
     * 고친 코드가 또 에러를 내면 웹훅 → 새 지시 → 또 에러로 끝없이 돈다.
     * 사람이 없는 시간에 그 고리가 돌면 아무도 멈추지 못하므로, 횟수로 끊는다.
     */
    public function canAttemptPatch(): bool
    {
        if ($this->isBusy()) {
            return false;
        }

        if (in_array($this->status, [self::STATUS_IGNORED, self::STATUS_BLOCKED], true)) {
            return false;
        }

        return $this->patch_attempts < (int) config('aiw.error_patch_max_attempts', 2);
    }
}
