<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceScreen extends Model
{
    protected $fillable = [
        'screen_key', 'name', 'blade_path', 'url_pattern', 'description', 'is_active', 'user_id',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MaintenanceVersion::class, 'screen_id')->orderByDesc('version_no');
    }

    public function latestVersion(): HasMany
    {
        return $this->hasMany(MaintenanceVersion::class, 'screen_id')->orderByDesc('version_no')->limit(1);
    }

    public function absoluteBladePath(): string
    {
        return base_path($this->blade_path);
    }

    public function bladeExists(): bool
    {
        return file_exists($this->absoluteBladePath());
    }
}
