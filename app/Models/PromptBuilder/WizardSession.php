<?php

namespace App\Models\PromptBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WizardSession extends Model
{
    protected $table = 'pb_wizard_sessions';

    protected $fillable = [
        'session_uuid', 'user_id', 'current_step', 'completed_steps',
        'context', 'purpose', 'input_sources', 'analysis_result',
        'generated_builders', 'approved_changes', 'status', 'expires_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'context' => 'array',
        'purpose' => 'array',
        'input_sources' => 'array',
        'analysis_result' => 'array',
        'generated_builders' => 'array',
        'approved_changes' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
