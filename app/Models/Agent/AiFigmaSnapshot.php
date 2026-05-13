<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFigmaSnapshot extends Model
{
    protected $table = 'ai_figma_snapshots';

    protected $fillable = [
        'figma_source_id',
        'snapshot_version',
        'raw_json_path',
        'normalized_json_path',
        'thumbnail_path',
        'checksum',
        'analyzed_at',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
    ];

    public function figmaSource(): BelongsTo
    {
        return $this->belongsTo(AiFigmaSource::class, 'figma_source_id');
    }
}
