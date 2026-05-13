<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DesktopToken extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'access_expires_at',
        'refresh_expires_at',
    ];

    protected $casts = [
        'access_expires_at'  => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function issue(User $user): array
    {
        // 기존 토큰 삭제 (사용자당 1개 유지)
        static::where('user_id', $user->id)->delete();

        $access  = Str::random(80);
        $refresh = Str::random(80);

        $token = static::create([
            'user_id'            => $user->id,
            'access_token'       => hash('sha256', $access),
            'refresh_token'      => hash('sha256', $refresh),
            'access_expires_at'  => now()->addHours(2),
            'refresh_expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token'  => $access,
            'refresh_token' => $refresh,
            'token_model'   => $token,
        ];
    }

    public static function findByAccessToken(string $raw): ?self
    {
        return static::where('access_token', hash('sha256', $raw))
            ->where('access_expires_at', '>', now())
            ->first();
    }

    public static function findByRefreshToken(string $raw): ?self
    {
        return static::where('refresh_token', hash('sha256', $raw))
            ->where('refresh_expires_at', '>', now())
            ->first();
    }
}
