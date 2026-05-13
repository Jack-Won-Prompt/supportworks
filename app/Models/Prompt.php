<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    protected $fillable = [
        'project_id', 'category_id', 'name', 'type', 'purpose', 'ai_role',
        'input_data', 'conditions', 'output_format', 'final_prompt',
        'confidence_score', 'status', 'is_active', 'source', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'confidence_score' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(PromptCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function executions()
    {
        return $this->hasMany(PromptExecution::class);
    }
}
