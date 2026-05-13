<?php

namespace App\Models\PromptBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardCandidate extends Model
{
    protected $table = 'pb_standard_candidates';

    protected $fillable = [
        'workspace_id', 'asset_type', 'name', 'content',
        'source', 'source_metadata', 'observation_count', 'status',
        'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'source_metadata' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
