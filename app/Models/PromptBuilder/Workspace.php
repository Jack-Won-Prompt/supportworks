<?php

namespace App\Models\PromptBuilder;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $table = 'pb_workspaces';

    protected $fillable = [
        'project_id', 'name', 'framework', 'framework_version',
        'language', 'styling', 'additional_config', 'is_default',
    ];

    protected $casts = [
        'additional_config' => 'array',
        'is_default' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function standardAssets(): HasMany
    {
        return $this->hasMany(StandardAsset::class);
    }

    public function standardCandidates(): HasMany
    {
        return $this->hasMany(StandardCandidate::class);
    }

    public function builders(): HasMany
    {
        return $this->hasMany(Builder::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(Sequence::class);
    }
}
