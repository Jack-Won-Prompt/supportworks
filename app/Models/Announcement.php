<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'body', 'type', 'is_active', 'starts_at', 'ends_at', 'created_by',
        'target_type', 'target_company_group_ids', 'send_email', 'email_sent_at', 'email_sent_count',
    ];

    protected $casts = [
        'is_active'                => 'boolean',
        'send_email'               => 'boolean',
        'starts_at'                => 'datetime',
        'ends_at'                  => 'datetime',
        'email_sent_at'            => 'datetime',
        'target_company_group_ids' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AdminUser::class, 'created_by');
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
