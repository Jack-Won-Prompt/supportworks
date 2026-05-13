<?php

namespace App\Models\PromptBuilder;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Builder extends Model
{
    protected $table = 'pb_builders';

    protected $fillable = [
        'project_id', 'workspace_id', 'user_id',
        'title', 'description', 'ai_type', 'purpose_type', 'purpose_targets',
        'figma_url', 'figma_file_path', 'input_source_files', 'input_images',
        'applied_standards', 'content', 'is_edited', 'current_version',
        'sequence_id', 'sequence_step_number', 'tags',
    ];

    protected $casts = [
        'purpose_targets' => 'array',
        'input_source_files' => 'array',
        'input_images' => 'array',
        'applied_standards' => 'array',
        'tags' => 'array',
        'is_edited' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BuilderVersion::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(Dependency::class, 'from_builder_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Dependency::class, 'to_builder_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(ExternalFeedback::class);
    }
}
