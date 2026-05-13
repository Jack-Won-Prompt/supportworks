<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentUserCredential extends Model
{
    protected $table = 'ai_agent_user_credentials';

    protected $fillable = [
        'user_id',
        'figma_pat_encrypted',
        'figma_pat_validated_at',
        'figma_pat_validation_status',
    ];

    protected $casts = [
        'figma_pat_encrypted'    => 'encrypted',
        'figma_pat_validated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFigmaPat(): ?string
    {
        return $this->figma_pat_encrypted;
    }

    public function setFigmaPat(string $pat): void
    {
        $this->figma_pat_encrypted         = $pat;
        $this->figma_pat_validated_at      = null;
        $this->figma_pat_validation_status = null;
    }

    public function isValid(): bool
    {
        return $this->figma_pat_validation_status === 'valid';
    }

    public function hasPat(): bool
    {
        return !empty($this->figma_pat_encrypted);
    }

    public function maskedPat(): string
    {
        if (!$this->hasPat()) return '';
        $pat = $this->getFigmaPat() ?? '';
        return substr($pat, 0, 8) . str_repeat('*', max(0, strlen($pat) - 8));
    }
}
