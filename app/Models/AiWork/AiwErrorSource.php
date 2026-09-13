<?php

namespace App\Models\AiWork;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 에러를 보내오는 운영 사이트 하나.
 *
 * 토큰이 곧 "어느 프로젝트인가" 다. 요청 본문의 project_id 는 믿지 않는다.
 */
class AiwErrorSource extends Model
{
    protected $table = 'aiw_error_sources';

    protected $fillable = ['project_id', 'name', 'token_hash', 'enabled', 'created_by'];

    /** 원문 토큰은 발급할 때 한 번만 보여 준다. 저장하는 것은 해시뿐이다. */
    protected $hidden = ['token_hash'];

    protected $casts = [
        'enabled'      => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(AiwErrorReport::class, 'source_id');
    }

    public static function generateToken(): string
    {
        return 'aiwerr_'.Str::random(48);
    }

    public static function hashToken(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /** 꺼 둔 것은 없는 것과 같다. 사고가 났을 때 토큰을 지우지 않고 끌 수 있다. */
    public static function findByToken(string $raw): ?self
    {
        return static::query()
            ->where('token_hash', static::hashToken($raw))
            ->where('enabled', true)
            ->first();
    }
}
