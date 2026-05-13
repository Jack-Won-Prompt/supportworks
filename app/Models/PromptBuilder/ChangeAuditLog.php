<?php

namespace App\Models\PromptBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeAuditLog extends Model
{
    protected $table = 'pb_change_audit_logs';

    protected $fillable = [
        'user_id', 'is_system_action', 'target_type', 'target_id',
        'change_type', 'reason', 'triggered_by',
        'before_state', 'after_state', 'diff',
        'affected_items', 'is_revertible', 'expires_at',
    ];

    protected $casts = [
        'is_system_action' => 'boolean',
        'before_state' => 'array',
        'after_state' => 'array',
        'affected_items' => 'array',
        'is_revertible' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
