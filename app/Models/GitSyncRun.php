<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitSyncRun extends Model
{
    protected $fillable = [
        'source', 'branch', 'since', 'until',
        'inserted', 'skipped', 'status', 'error_message', 'triggered_by',
    ];

    protected $casts = [
        'since' => 'datetime',
        'until' => 'datetime',
    ];

    public function triggerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
