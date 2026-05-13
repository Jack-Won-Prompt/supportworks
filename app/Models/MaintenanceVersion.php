<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceVersion extends Model
{
    protected $fillable = [
        'screen_id', 'version_no', 'change_summary', 'files', 'prompt',
        'user_request', 'status', 'applied_at', 'applied_by',
    ];

    protected $casts = [
        'files'      => 'array',
        'prompt'     => 'array',
        'applied_at' => 'datetime',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(MaintenanceScreen::class, 'screen_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }
}
