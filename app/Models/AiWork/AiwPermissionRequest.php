<?php

namespace App\Models\AiWork;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 툴 실행 승인 요청. request_key 가 unique 라 재요청이 레코드를 늘리지 않는다. */
class AiwPermissionRequest extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'job_id', 'request_key', 'tool_name', 'tool_input',
        'status', 'decided_by', 'decided_at', 'deny_reason',
    ];

    protected $casts = [
        'tool_input' => 'array',
        'decided_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDecided(): bool
    {
        return in_array($this->status, ['allowed', 'denied', 'expired'], true);
    }

    /** 데몬에 돌려줄 결정. expired 는 거부로 취급한다. */
    public function decisionForDaemon(): array
    {
        return match ($this->status) {
            'allowed' => ['behavior' => 'allow'],
            'denied'  => ['behavior' => 'deny', 'message' => $this->deny_reason ?? '사용자가 거부했습니다.'],
            'expired' => ['behavior' => 'deny', 'message' => 'timeout'],
            default   => ['behavior' => 'pending'],
        };
    }
}
