<?php

namespace App\Models\WorksBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $table = 'wb_notification_settings';

    protected $fillable = ['user_id', 'stage_code', 'channel', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isEnabled(int $userId, string $stageCode, string $channel): bool
    {
        $row = self::where('user_id', $userId)
            ->where('stage_code', $stageCode)
            ->where('channel', $channel)
            ->first();
        return $row ? $row->enabled : true;
    }
}
