<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property int             $system_error_log_id
 * @property string          $status
 * @property string|null     $decision
 * @property array|null      $red_signals
 * @property array|null      $yellow_signals
 * @property string|null     $decision_reason
 * @property string|null     $blocked_path
 * @property string|null     $branch_name
 * @property string|null     $worktree_path
 * @property string|null     $proposed_fix_summary
 * @property array|null      $changed_files
 * @property array|null      $test_result
 * @property string|null     $pr_url
 * @property string|null     $deployed_commit
 * @property string|null     $deploy_log
 * @property int|null        $approved_by_admin_id
 * @property \Carbon\Carbon|null $escalated_at
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $deployed_at
 * @property \Carbon\Carbon|null $finished_at
 * @property string|null     $error_message
 * @property int             $retry_count
 */
class AiFixJob extends Model
{
    // ── 상태 상수 (state machine) ────────────────────────────────────────────
    public const STATUS_PENDING           = 'pending';
    public const STATUS_ANALYZING         = 'analyzing';
    public const STATUS_BLOCKED           = 'blocked';
    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';
    public const STATUS_AUTO_APPROVED     = 'auto_approved';
    public const STATUS_APPLYING          = 'applying';
    public const STATUS_TESTING           = 'testing';
    public const STATUS_TESTS_FAILED      = 'tests_failed';
    public const STATUS_READY_TO_DEPLOY   = 'ready_to_deploy';
    public const STATUS_DEPLOYING         = 'deploying';
    public const STATUS_DEPLOYED          = 'deployed';
    public const STATUS_DEPLOY_FAILED     = 'deploy_failed';
    public const STATUS_ROLLED_BACK       = 'rolled_back';
    public const STATUS_REJECTED          = 'rejected';
    public const STATUS_CANCELLED         = 'cancelled';

    // 유효한 다음 상태 전이. evaluator/orchestrator 가 검증에 사용.
    public const TRANSITIONS = [
        self::STATUS_PENDING           => [self::STATUS_ANALYZING, self::STATUS_CANCELLED],
        self::STATUS_ANALYZING         => [self::STATUS_BLOCKED, self::STATUS_AWAITING_APPROVAL, self::STATUS_AUTO_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_AWAITING_APPROVAL => [self::STATUS_APPLYING, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_AUTO_APPROVED     => [self::STATUS_APPLYING, self::STATUS_CANCELLED],
        self::STATUS_APPLYING          => [self::STATUS_TESTING, self::STATUS_TESTS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_TESTING           => [self::STATUS_READY_TO_DEPLOY, self::STATUS_TESTS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_READY_TO_DEPLOY   => [self::STATUS_DEPLOYING, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_DEPLOYING         => [self::STATUS_DEPLOYED, self::STATUS_DEPLOY_FAILED, self::STATUS_ROLLED_BACK],
        // 터미널 상태들 — 다음 전이 없음
        self::STATUS_BLOCKED         => [],
        self::STATUS_TESTS_FAILED    => [],
        self::STATUS_DEPLOYED        => [],
        self::STATUS_DEPLOY_FAILED   => [],
        self::STATUS_ROLLED_BACK     => [],
        self::STATUS_REJECTED        => [],
        self::STATUS_CANCELLED       => [],
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_BLOCKED,
        self::STATUS_TESTS_FAILED,
        self::STATUS_DEPLOYED,
        self::STATUS_DEPLOY_FAILED,
        self::STATUS_ROLLED_BACK,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'system_error_log_id', 'status',
        'decision', 'red_signals', 'yellow_signals', 'decision_reason', 'blocked_path',
        'branch_name', 'worktree_path',
        'proposed_fix_summary', 'changed_files', 'test_result', 'pr_url',
        'deployed_commit', 'deploy_log',
        'approved_by_admin_id', 'escalated_at', 'approved_at', 'deployed_at', 'finished_at',
        'error_message', 'retry_count',
    ];

    protected $casts = [
        'red_signals'     => 'array',
        'yellow_signals'  => 'array',
        'changed_files'   => 'array',
        'test_result'     => 'array',
        'escalated_at'    => 'datetime',
        'approved_at'     => 'datetime',
        'deployed_at'     => 'datetime',
        'finished_at'     => 'datetime',
    ];

    // ── 관계 ────────────────────────────────────────────────────────────────
    public function systemErrorLog(): BelongsTo
    {
        return $this->belongsTo(SystemErrorLog::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'approved_by_admin_id');
    }

    // ── 스코프 ──────────────────────────────────────────────────────────────
    public function scopeAwaitingApproval(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_AWAITING_APPROVAL);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function scopeTerminal(Builder $q): Builder
    {
        return $q->whereIn('status', self::TERMINAL_STATUSES);
    }

    // ── 상태 전이 ───────────────────────────────────────────────────────────
    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function canTransitionTo(string $next): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? null;
        if ($allowed === null) return false;   // unknown current
        return in_array($next, $allowed, true);
    }

    /**
     * 안전한 상태 전이. 잘못된 전이는 InvalidStateTransition 예외.
     * 터미널 상태 진입 시 finished_at 자동 기록.
     */
    public function transitionTo(string $next, array $extraAttrs = []): self
    {
        if (!$this->canTransitionTo($next)) {
            throw new \DomainException(
                "Invalid transition: {$this->status} -> {$next}"
            );
        }

        $this->status = $next;
        foreach ($extraAttrs as $k => $v) {
            $this->{$k} = $v;
        }

        if (in_array($next, self::TERMINAL_STATUSES, true) && $this->finished_at === null) {
            $this->finished_at = now();
        }

        $this->save();
        return $this;
    }
}
