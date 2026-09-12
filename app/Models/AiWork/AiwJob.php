<?php

namespace App\Models\AiWork;

use App\Enums\AiWork\AiwFailureCode;
use App\Enums\AiWork\AiwJobStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * AI 작업 지시.
 *
 * 종료 상태(completed/failed/cancelled)가 되면 지시 내용은 불변이다. 이어서
 * 작업하려면 후속 지시(parent_job_id 를 가진 새 job)를 만든다. 감사 추적이
 * 깨지지 않게 하기 위함이다.
 */
class AiwJob extends Model
{
    use HasFactory;

    /** 종료 후 변경이 금지되는 컬럼. */
    public const IMMUTABLE_AFTER_TERMINAL = [
        'instruction', 'title', 'allowed_tools', 'mode', 'model', 'cost_limit_usd',
    ];

    protected $fillable = [
        'project_id', 'agent_id', 'parent_job_id',
        'title', 'instruction', 'mode', 'model', 'context_limit_tokens',
        'allowed_tools', 'permission_mode', 'cost_limit_usd', 'use_branch',
        'auto_deploy', 'auto_deploy_target_id',
        'status', 'session_chain', 'handover_count', 'context_tokens',
        'result_summary', 'changed_files', 'git_diff', 'git_diff_path',
        'error_code', 'error_detail',
        'cost_usd', 'duration_ms', 'error_message',
        'created_by', 'dispatched_at', 'started_at', 'finished_at',
    ];

    /**
     * 마이그레이션의 DB 기본값과 같은 값을 모델에도 둔다.
     * 이게 없으면 create() 에서 컬럼을 생략했을 때 refresh 전까지 속성이 null 이라
     * branchName() 같은 파생 값이 실제 저장 결과와 어긋난다.
     */
    protected $attributes = [
        'mode'            => 'interactive',
        'permission_mode' => 'acceptEdits',
        'use_branch'      => true,
        'status'          => 'queued',
        'handover_count'  => 0,
        'context_tokens'  => 0,
        'cost_usd'        => 0,
    ];

    protected $casts = [
        'status'               => AiwJobStatus::class,
        'error_detail'         => 'array',
        'allowed_tools'        => 'array',
        'session_chain'        => 'array',
        'changed_files'        => 'array',
        'use_branch'           => 'boolean',
        'auto_deploy'          => 'boolean',
        'cost_limit_usd'       => 'decimal:4',
        'cost_usd'             => 'decimal:4',
        'context_limit_tokens' => 'integer',
        'context_tokens'       => 'integer',
        'handover_count'       => 'integer',
        'duration_ms'          => 'integer',
        'dispatched_at'        => 'datetime',
        'started_at'           => 'datetime',
        'finished_at'          => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $job) {
            $job->guardTerminalImmutability();
            $job->guardCostLimit();
        });
    }

    /**
     * 종료된 job 의 지시 내용 변경을 차단한다.
     *
     * 이 저장에서 종료 상태로 '전이하는' 경우는 허용한다 — 결과(result_summary,
     * git_diff 등)를 함께 기록해야 하기 때문이다. 이미 종료된 job 을 다시
     * 건드리는 경우만 막는다.
     */
    private function guardTerminalImmutability(): void
    {
        if (! $this->exists) {
            return;
        }

        $original = $this->getOriginal('status');
        $originalStatus = $original instanceof AiwJobStatus
            ? $original
            : AiwJobStatus::tryFrom((string) $original);

        if ($originalStatus === null || ! $originalStatus->isTerminal()) {
            return;
        }

        $changed = array_intersect(array_keys($this->getDirty()), self::IMMUTABLE_AFTER_TERMINAL);

        if ($changed !== []) {
            throw new RuntimeException(sprintf(
                '종료된 작업 지시(#%s, %s)는 수정할 수 없습니다. 변경 시도: %s. 후속 지시를 생성하세요.',
                $this->getKey(),
                $originalStatus->value,
                implode(', ', $changed),
            ));
        }
    }

    /**
     * 상한을 적으려면 0 보다 커야 한다. 비워 두는 것(null)은 "제한 없음" 이라
     * 허용하지만, 0 이나 음수는 실수로 보고 막는다.
     */
    private function guardCostLimit(): void
    {
        if ($this->cost_limit_usd !== null && (float) $this->cost_limit_usd <= 0) {
            throw new RuntimeException('cost_limit_usd 는 0 보다 커야 합니다.');
        }
    }

    // ── 관계 ────────────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiwAgent::class, 'agent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_job_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_job_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AiwJobLog::class, 'job_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiwJobMessage::class, 'job_id');
    }

    public function autoDeployTarget(): BelongsTo
    {
        return $this->belongsTo(AiwDeployTarget::class, 'auto_deploy_target_id');
    }

    public function publishes(): HasMany
    {
        return $this->hasMany(AiwPublish::class, 'job_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AiwJobAttachment::class, 'job_id');
    }

    public function permissionRequests(): HasMany
    {
        return $this->hasMany(AiwPermissionRequest::class, 'job_id');
    }

    // ── 세션 ────────────────────────────────────────────────────────────────

    /** 현재 세션 id. session_chain 의 마지막 요소. */
    /**
     * 이어붙이기에 쓸 수 있는 마지막 세션 ID.
     *
     * 데몬이 session_id 를 받기 전에 보고하면 'unknown' 이 기록된다(과거 버그).
     * 그 값으로 resume 을 시도하면 Claude Code 가
     * "--resume ... is not a UUID" 로 죽으므로, 쓸 수 없는 값은 없는 것으로 본다.
     */
    public function currentSessionId(): ?string
    {
        $chain = $this->session_chain ?? [];
        $last = end($chain);
        $id = is_array($last) ? ($last['session_id'] ?? null) : null;

        if (! is_string($id) || $id === '' || $id === 'unknown') {
            return null;
        }

        return $id;
    }

    public function currentSessionIndex(): int
    {
        return max(0, count($this->session_chain ?? []) - 1);
    }

    // ── 비용·컨텍스트 ───────────────────────────────────────────────────────

    /**
     * 상한을 넘었는가. **상한이 없으면(null) 영원히 false 다.**
     *
     * 구독 로그인으로 도는 담당자는 화면의 금액이 실제 청구가 아니라 환산값이다.
     * 그래서 상한을 비워 두는 선택을 허용한다 — 폭주는 데몬의 시간 제한
     * (JOB_TIMEOUT_SEC / SESSION_MAX_SEC)이 막는다.
     */
    public function isOverCostLimit(): bool
    {
        if ($this->cost_limit_usd === null) {
            return false;
        }

        return (float) $this->cost_usd >= (float) $this->cost_limit_usd;
    }

    /** 상한 없이 도는 작업인가. 화면이 "제한 없음" 으로 표시한다. */
    public function hasNoCostLimit(): bool
    {
        return $this->cost_limit_usd === null;
    }

    /** 0~1. UI 게이지에 쓴다. */
    public function contextRatio(): float
    {
        $limit = (int) $this->context_limit_tokens;

        return $limit > 0 ? min(1.0, $this->context_tokens / $limit) : 0.0;
    }

    public function costRatio(): float
    {
        $limit = (float) $this->cost_limit_usd;

        return $limit > 0 ? min(1.0, (float) $this->cost_usd / $limit) : 0.0;
    }

    /** 실패 사유 코드. 화면이 복구 버튼을 고르는 데 쓴다. */
    public function failureCode(): ?AiwFailureCode
    {
        return $this->error_code ? AiwFailureCode::tryFrom($this->error_code) : null;
    }

    public function branchName(): ?string
    {
        return $this->use_branch ? 'aiw/job-'.$this->getKey() : null;
    }
}
