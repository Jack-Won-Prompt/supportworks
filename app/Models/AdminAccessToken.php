<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminAccessToken extends Model
{
    protected $fillable = [
        'admin_user_id', 'access_token', 'refresh_token',
        'access_expires_at', 'refresh_expires_at', 'ip_address', 'revoked_at',
    ];

    protected $casts = [
        'access_expires_at'  => 'datetime',
        'refresh_expires_at' => 'datetime',
        'revoked_at'         => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public static function issue(AdminUser $admin, ?string $ip = null): array
    {
        // 사용자당 토큰 1개 유지
        static::where('admin_user_id', $admin->id)->delete();

        $access  = Str::random(80);
        $refresh = Str::random(80);

        static::create([
            'admin_user_id'      => $admin->id,
            'access_token'       => hash('sha256', $access),
            'refresh_token'      => hash('sha256', $refresh),
            'access_expires_at'  => now()->addHours(2),
            'refresh_expires_at' => now()->addDays(30),
            'ip_address'         => $ip,
        ]);

        return ['access_token' => $access, 'refresh_token' => $refresh];
    }

    public static function findByAccessToken(string $raw): ?self
    {
        return static::where('access_token', hash('sha256', $raw))
            ->whereNull('revoked_at')
            ->where('access_expires_at', '>', now())
            ->first();
    }

    public static function findByRefreshToken(string $raw): ?self
    {
        return static::where('refresh_token', hash('sha256', $raw))
            ->whereNull('revoked_at')
            ->where('refresh_expires_at', '>', now())
            ->first();
    }
}
