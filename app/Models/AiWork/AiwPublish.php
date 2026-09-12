<?php

namespace App\Models\AiWork;

use App\Events\AiWork\JobArtifactsChanged;
use Illuminate\Support\Facades\Log;
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
        'job_id', 'requested_by', 'automatic', 'source_branch', 'target_branch',
        'commit_message', 'status', 'output', 'commit_sha', 'finished_at',
    ];

    protected $casts = [
        'automatic'   => 'boolean',
        'created_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * 화면의 카드가 멈춰 있지 않게 한다.
     *
     * 이 표의 상태가 바뀌는 경로는 여러 개다(사람이 누른 버튼, 자동 진행,
     * 담당자 보고, 큐 작업). 각 경로에서 따로 알리면 언젠가 하나를 빠뜨린다 —
     * 실제로 로그만 흐르고 카드는 "진행 중…" 인 채로 남았다.
     */
    protected static function booted(): void
    {
        $notify = function (self $row) {
            if (! $row->job_id) {
                return;
            }

            // 알림이 실패해도 저장은 지켜야 한다. Reverb 가 꺼져 있거나 닿지
            // 않으면 event() 가 예외를 던지는데, 그게 배포·푸시 기록 저장까지
            // 무너뜨리면 안 된다(이 저장소가 쓰는 방식과 같다).
            try {
                event(new JobArtifactsChanged((int) $row->job_id));
            } catch (\Throwable $e) {
                Log::warning('AI Works: 카드 갱신 알림 실패(기록은 저장됨)', [
                    'job_id' => $row->job_id,
                    'error'  => $e->getMessage(),
                ]);
            }
        };

        static::created($notify);
        static::updated($notify);
    }

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
