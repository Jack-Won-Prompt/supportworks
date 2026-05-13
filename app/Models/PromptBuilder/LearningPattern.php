<?php

namespace App\Models\PromptBuilder;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPattern extends Model
{
    protected $table = 'pb_learning_patterns';

    protected $fillable = [
        'project_id', 'pattern_id', 'pattern_name', 'category',
        'description', 'observation_count',
        'first_observed_at', 'last_observed_at',
        'observed_in_feedbacks', 'reached_threshold',
        'user_decision', 'decided_at',
    ];

    protected $casts = [
        'observed_in_feedbacks' => 'array',
        'reached_threshold' => 'boolean',
        'first_observed_at' => 'datetime',
        'last_observed_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
