<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FigmaFile extends Model
{
    protected $fillable = ['user_id', 'name', 'url', 'file_key', 'thumbnail_url', 'last_synced_at'];

    protected $casts = ['last_synced_at' => 'datetime'];

    public function sessions(): HasMany
    {
        return $this->hasMany(AiSession::class, 'figma_file_id');
    }

    public static function extractKey(string $url): ?string
    {
        // https://www.figma.com/file/{KEY}/... or /design/{KEY}/...
        if (preg_match('#figma\.com/(?:file|design)/([A-Za-z0-9]+)#', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
