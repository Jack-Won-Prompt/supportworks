<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Requirement;
use App\Models\PlanningDoc;

class ProjectFeatureSuggestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'title', 'description', 'reason', 'created_by',
        'is_applied', 'applied_at',
        'requirement_id', 'planning_doc_id', 'inserted_markdown',
    ];

    protected $casts = [
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }

    public function planningDoc()
    {
        return $this->belongsTo(PlanningDoc::class, 'planning_doc_id');
    }
}
