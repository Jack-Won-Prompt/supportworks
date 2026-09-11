<?php

namespace App\Models\AiWork;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 커밋·푸시 요청 한 건. 원격을 바꾸는 동작이라 기록이 남는다. */
class AiwPublish extends Model
{
    public const UPDATED_AT = null;

    /** 출력이 길면 화면과 DB 를 통째로 먹는다. 뒤쪽(대개 오류)을 남긴다. */
    public const MAX_OUTPUT = 20000;

    protected $fillable = [
        'job_id', 'requested_by', 'source_branch', 'target_branch',
        'commit_message', 'status', 'output', 'commit_sha', 'finished_at',
    ];

    protected $casts = [
        'created_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['succeeded', 'failed'], true);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'running'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => '대기',
            'running'   => '진행 중',
            'succeeded' => '완료',
            'failed'    => '실패',
            default     => $this->status,
        };
    }

    /** 뒤쪽을 남긴다 — git 오류는 끝에 나온다. */
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
