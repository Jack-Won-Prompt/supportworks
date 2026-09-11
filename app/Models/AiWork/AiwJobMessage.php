<?php

namespace App\Models\AiWork;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 대화 흐름. logs 와는 별도 시퀀스를 쓴다. */
class AiwJobMessage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'job_id', 'seq', 'role', 'content', 'choices', 'user_id', 'session_index', 'delivered_at',
    ];

    protected $casts = [
        'seq'           => 'integer',
        'choices'       => 'array',
        'session_index' => 'integer',
        'delivered_at'  => 'datetime',
        'created_at'    => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(AiwJob::class, 'job_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 데몬이 아직 세션에 주입하지 않은 사용자 메시지(= UI 의 "전달 대기"). */
    public function scopeUndelivered($query)
    {
        return $query->where('role', 'user')->whereNull('delivered_at');
    }

    public function isPendingDelivery(): bool
    {
        return $this->role === 'user' && $this->delivered_at === null;
    }
}
