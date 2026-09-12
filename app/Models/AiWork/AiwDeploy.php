<?php

namespace App\Models\AiWork;

use App\Events\AiWork\JobArtifactsChanged;
use Illuminate\Support\Facades\Log;
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
        'target_id', 'job_id', 'requested_by', 'automatic', 'status',
        'exit_code', 'output', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'automatic'   => 'boolean',
        'exit_code'   => 'integer',
        'created_at'  => 'datetime',
        'started_at'  => 'datetime',
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
