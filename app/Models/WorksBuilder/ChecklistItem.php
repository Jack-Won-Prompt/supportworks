<?php

namespace App\Models\WorksBuilder;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 명세 v11 §2.7 — ChecklistItem (프로젝트별).
 */
class ChecklistItem extends Model
{
    protected $table = 'wb_checklist_items';

    protected $fillable = [
        'project_id', 'category', 'title', 'description',
        'check_prompt_text',
        'added_from_task_id', 'added_from_round',
        'added_at', 'version', 'is_active',
    ];

    protected $casts = [
        'added_at'  => 'datetime',
        'version'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
