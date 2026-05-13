<?php

namespace App\Models\PromptBuilder;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    protected $table = 'pb_templates';

    protected $fillable = [
        'owner_id', 'project_id', 'name', 'description', 'tags',
        'share_scope', 'context_template', 'purpose_template',
        'builder_structure', 'usage_count', 'last_used_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'context_template' => 'array',
        'purpose_template' => 'array',
        'builder_structure' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
