<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['maintenance_mode', 'maintenance_message'];

    protected $casts = [
        'maintenance_mode' => 'boolean',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([]);
    }

    public static function isMaintenanceMode(): bool
    {
        return (bool) self::current()->maintenance_mode;
    }
}
