<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamsSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'client_id', 'client_secret',
        'access_token', 'token_expires_at', 'is_verified',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_verified'      => 'boolean',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], []);
    }

    public function hasCredentials(): bool
    {
        return filled($this->tenant_id) && filled($this->client_id) && filled($this->client_secret);
    }

    public function getDecryptedSecret(): ?string
    {
        return $this->client_secret ? decrypt($this->client_secret) : null;
    }

    public function getDecryptedToken(): ?string
    {
        return $this->access_token ? decrypt($this->access_token) : null;
    }

    public function isTokenValid(): bool
    {
        return $this->access_token
            && $this->token_expires_at
            && $this->token_expires_at->isFuture();
    }
}
