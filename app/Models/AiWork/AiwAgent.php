<?php

namespace App\Models\AiWork;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 작업 PC(데몬).
 *
 * status 컬럼이 없다. 온라인 여부는 last_seen_at 으로 계산한다 — 저장된 상태가
 * 실제와 어긋날 여지를 없애고 판정 스케줄러도 필요 없게 한다.
 */
class AiwAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'token_hash', 'user_id',
        'expires_at', 'allowed_ips', 'last_used_ip', 'last_seen_at', 'capabilities',
    ];

    protected $casts = [
        'allowed_ips'  => 'array',
        'capabilities' => 'array',
        'expires_at'   => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /** token_hash 는 노출하지 않는다. */
    protected $hidden = ['token_hash'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agentProjects(): HasMany
    {
        return $this->hasMany(AiwAgentProject::class, 'agent_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AiwJob::class, 'agent_id');
    }

    // ── 토큰 ────────────────────────────────────────────────────────────────

    /** 원문 토큰을 만들고 해시만 모델에 넣는다. 원문은 호출자가 1회 표시하고 버린다. */
    public static function generateToken(): string
    {
        return 'aiw_'.Str::random(48);
    }

    public static function hashToken(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /** 원문 토큰으로 조회. 만료된 토큰은 찾지 못한 것으로 취급한다. */
    public static function findByToken(string $raw): ?self
    {
        return static::query()
            ->where('token_hash', static::hashToken($raw))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** allowed_ips 가 비어 있으면 제한 없음. */
    public function allowsIp(?string $ip): bool
    {
        $allowed = $this->allowed_ips;

        if (empty($allowed) || $ip === null) {
            return empty($allowed);
        }

        foreach ($allowed as $rule) {
            if ($this->ipMatches($ip, (string) $rule)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatches(string $ip, string $rule): bool
    {
        if (! str_contains($rule, '/')) {
            return $ip === $rule;
        }

        [$subnet, $bits] = explode('/', $rule, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;   // IPv6 CIDR 는 지원하지 않는다(필요해지면 확장)
        }

        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    // ── 과금 주체 ───────────────────────────────────────────────────────────

    /**
     * ANTHROPIC_API_KEY 로 도는가(= 실제 종량 과금), 아니면 그 PC 의 Claude Code
     * 로그인으로 도는가(= 구독에 포함).
     *
     * 데몬이 하트비트의 capabilities.auth_mode 로 알려준다. 값이 없으면(구버전
     * 데몬) 추정치 쪽으로 보수적으로 읽는다 — 실제 청구액이라고 잘못 말하는 것보다
     * 낫다.
     */
    public function usesApiKey(): bool
    {
        return ($this->capabilities['auth_mode'] ?? null) === 'api_key';
    }

    /** 화면에 쓸 비용 라벨. 구독 모드에서는 청구액이 아니라 사용량 지표다. */
    public function costLabel(): string
    {
        return $this->usesApiKey() ? '비용' : '예상 사용량';
    }

    public function costHint(): string
    {
        return $this->usesApiKey()
            ? 'ANTHROPIC_API_KEY 로 실행되어 실제 청구되는 금액입니다.'
            : '이 작업 PC 는 구독 로그인으로 실행됩니다. API 로 썼다면 얼마였을지의 추정치이며 실제 청구액이 아닙니다.';
    }

    // ── 온라인 판정 ─────────────────────────────────────────────────────────

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds((int) config('aiw.offline_after_sec', 90)));
    }

    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>', now()->subSeconds((int) config('aiw.offline_after_sec', 90)));
    }
}
