<?php

namespace App\Models\Agent;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiFigmaSource extends Model
{
    protected $table = 'ai_figma_sources';

    public const STATUS_DISCONNECTED = 'disconnected';
    public const STATUS_CONNECTED    = 'connected';
    public const STATUS_INVALID      = 'invalid';
    public const STATUS_UNAUTHORIZED = 'unauthorized';
    public const STATUS_UNREACHABLE  = 'unreachable';

    public const SOURCE_FIGMA_URL       = 'figma_url';
    public const SOURCE_PROJECT_FILE    = 'project_file';
    public const SOURCE_EXISTING_SOURCE = 'existing_source';

    protected $fillable = [
        'project_id',
        'session_id',
        'source_type',
        'figma_url',
        'figma_file_key',
        'figma_node_id',
        'figma_version',
        'oauth_user_id',
        'status',
        'last_error',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AiAgentSession::class, 'session_id');
    }

    public function oauthUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oauth_user_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(AiFigmaSnapshot::class, 'figma_source_id')->orderByDesc('snapshot_version');
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(AiFigmaSnapshot::class, 'figma_source_id')->latestOfMany('snapshot_version');
    }

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }
}
