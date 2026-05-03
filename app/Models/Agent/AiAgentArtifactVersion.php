<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentArtifactVersion extends Model
{
    protected $table = 'ai_agent_artifact_versions';

    protected $fillable = [
        'artifact_id',
        'version',
        'content',
        'meta',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
