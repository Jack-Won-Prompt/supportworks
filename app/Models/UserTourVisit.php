<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTourVisit extends Model
{
    protected $fillable = ['user_id', 'tour_key', 'visited_at'];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    /**
     * 특정 사용자가 해당 투어를 본 적이 있는지.
     */
    public static function hasVisited(int $userId, string $tourKey): bool
    {
        return static::where('user_id', $userId)->where('tour_key', $tourKey)->exists();
    }

    /**
     * visited 기록 (멱등) — 이미 있으면 그대로 둠.
     */
    public static function markVisited(int $userId, string $tourKey): void
    {
        static::firstOrCreate(
            ['user_id' => $userId, 'tour_key' => $tourKey],
            ['visited_at' => now()],
        );
    }
}
