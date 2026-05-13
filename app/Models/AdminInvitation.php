<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminInvitation extends Model
{
    protected $fillable = [
        'email', 'name', 'role', 'token', 'status',
        'invited_by', 'company_group_ids', 'expires_at',
    ];

    protected $casts = [
        'expires_at'        => 'datetime',
        'company_group_ids' => 'array',
    ];

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'invited_by');
    }

    public static function createInvitation(AdminUser $inviter, array $data): array
    {
        $raw = Str::random(80);

        $invitation = static::create([
            'email'             => $data['email'],
            'name'              => $data['name'],
            'role'              => $data['role'],
            'token'             => hash('sha256', $raw),
            'status'            => 'invited',
            'invited_by'        => $inviter->id,
            'company_group_ids' => $data['company_group_ids'] ?? [],
            'expires_at'        => now()->addHours(72),
        ]);

        return ['invitation' => $invitation, 'raw_token' => $raw];
    }

    public static function findValidToken(string $raw): ?self
    {
        return static::where('token', hash('sha256', $raw))
            ->where('status', 'invited')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
