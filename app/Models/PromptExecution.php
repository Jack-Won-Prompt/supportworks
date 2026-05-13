<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptExecution extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'prompt_id', 'project_id',
        'raw_input', 'refined_prompt', 'ai_response',
        'html_output', 'css_output', 'js_output', 'status', 'ai_provider',
    ];

    protected $casts = [
        'refined_prompt' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(AiSession::class, 'session_id');
    }

    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->hasMany(ExecutionFile::class, 'execution_id');
    }

    public function inputFiles()
    {
        return $this->hasMany(ExecutionFile::class, 'execution_id')->where('type', 'input');
    }

    public function outputFiles()
    {
        return $this->hasMany(ExecutionFile::class, 'execution_id')->where('type', 'output');
    }
}
