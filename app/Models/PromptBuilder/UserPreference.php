<?php

namespace App\Models\PromptBuilder;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $table = 'pb_user_preferences';

    protected $fillable = [
        'user_id', 'last_project_id', 'last_used_at', 'last_ai_type',
        'per_project_workspace', 'auto_select_project', 'auto_select_workspace',
        'auto_select_ai', 'expiration_days', 'skip_confirm_dialogs',
    ];

    protected $casts = [
        'per_project_workspace' => 'array',
        'auto_select_project' => 'boolean',
        'auto_select_workspace' => 'boolean',
        'auto_select_ai' => 'boolean',
        'skip_confirm_dialogs' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'last_project_id');
    }
}
