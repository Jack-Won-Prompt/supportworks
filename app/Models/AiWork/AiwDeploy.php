<?php

namespace App\Models\AiWork;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 배포 실행 한 건. 되돌리기 비용이 큰 동작이라 출력까지 전부 남긴다. */
class AiwDeploy extends Model
{
    public const UPDATED_AT = null;

    /** 출력 상한. 넘치면 뒤쪽(대개 오류)을 남긴다. */
    public const MAX_OUTPUT = 200000;

    protected $fillable = [
        'target_id', 'job_id', 'requested_by', 'status',
        'exit_code', 'output', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'exit_code'   => 'integer',
        'created_at'  => 'datetime',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(AiwDeployTarget::class, 'target_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'queued'    => '대기',
            'running'   => '실행 중',
            'succeeded' => '성공',
            'failed'    => '실패',
            default     => $this->status,
        };
    }

    public function duration(): ?string
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->finished_at).'초';
    }

    public static function truncateOutput(?string $output): ?string
    {
        if ($output === null) {
            return null;
        }

        return strlen($output) > self::MAX_OUTPUT
            ? '... (앞부분 생략)'.PHP_EOL.substr($output, -self::MAX_OUTPUT)
            : $output;
    }
}
