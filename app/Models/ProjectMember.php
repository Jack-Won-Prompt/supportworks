<?php

namespace App\Models;

use App\Models\Builders\ProjectMemberBuilder;
use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    public function newEloquentBuilder($query): ProjectMemberBuilder
    {
        return new ProjectMemberBuilder($query);
    }

    protected $fillable = ['project_id', 'user_id', 'role'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'manager' => '매니저',
            'member' => '멤버',
            'viewer' => '열람자',
            default => '멤버',
        };
    }
}
