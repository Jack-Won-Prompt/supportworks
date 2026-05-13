<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptCategory extends Model
{
    protected $fillable = ['project_id', 'name', 'source', 'is_approved', 'created_by'];

    protected $casts = ['is_approved' => 'boolean'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prompts()
    {
        return $this->hasMany(Prompt::class, 'category_id');
    }
}
