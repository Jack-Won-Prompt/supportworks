<?php

namespace App\Models\PromptBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardAsset extends Model
{
    protected $table = 'pb_standard_assets';

    protected $fillable = [
        'workspace_id', 'asset_type', 'name', 'version',
        'content', 'metadata', 'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
