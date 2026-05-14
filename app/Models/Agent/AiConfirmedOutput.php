<?php

namespace App\Models\Agent;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConfirmedOutput extends Model
{
    protected $table = 'ai_confirmed_outputs';

    protected $fillable = [
        'project_id',
        'output_id',
        'confirmed_by',
        'confirmed_at',
        'summary',
        'context_meta',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'context_meta' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function output(): BelongsTo
    {
        return $this->belongsTo(AiOutput::class, 'output_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeForProject(Builder $q, int $projectId): Builder
    {
        return $q->where('project_id', $projectId);
    }
}
