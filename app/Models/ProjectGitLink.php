<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGitLink extends Model
{
    public const ALLOWED_SOURCE = 'withworks';
    public const ALLOWED_REPO   = 'dhlogitsticsPlatform/withworks';

    protected $fillable = ['project_id', 'source', 'repo', 'path_prefix', 'linked_by'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function linker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
