<?php

namespace App\Models\PromptBuilder;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    protected $table = 'pb_sequences';

    protected $fillable = [
        'project_id', 'workspace_id', 'owner_id',
        'name', 'description', 'ai_type',
        'current_step', 'completed_steps', 'status',
    ];

    protected $casts = [
        'completed_steps' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function builders(): HasMany
    {
        return $this->hasMany(Builder::class)->orderBy('sequence_step_number');
    }
}
