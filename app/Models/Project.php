<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 'description', 'status', 'start_date', 'end_date',
        'created_by', 'client_name', 'client_email', 'company_group_id',
        'si_mode_enabled', 'sm_mode_enabled', 'preferred_llm_provider', 'preferred_llm_model',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'si_mode_enabled' => 'boolean',
        'sm_mode_enabled' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function companyGroup()
    {
        return $this->belongsTo(\App\Models\CompanyGroup::class);
    }

    // 로컬 스코프: 특정 사용자 회사 범위 내 프로젝트만 ─────────────────────
    public function scopeCompanyOf(Builder $query, User $user): Builder
    {
        if ($user->company_group_id) {
            return $query->where('company_group_id', $user->company_group_id);
        }
        return $query->where('created_by', $user->id)->whereNull('company_group_id');
    }

    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function myMembership()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class)->orderBy('display_order');
    }

    public function taskGroups()
    {
        return $this->hasMany(TaskGroup::class)->orderBy('display_order');
    }

    public function subTasks()
    {
        return $this->hasMany(SubTask::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function fileCategories()
    {
        return $this->hasMany(\App\Models\ProjectFileCategory::class)->orderBy('sort_order');
    }

    public function maintenanceFileCategories()
    {
        return $this->hasMany(\App\Models\MaintenanceFileCategory::class)->orderBy('sort_order');
    }

    public function planningDocs()
    {
        return $this->hasMany(PlanningDoc::class);
    }

    public function urs()
    {
        return $this->hasMany(\App\Models\ProjectUrs::class);
    }

    public function maintenances()
    {
        return $this->hasMany(ProjectMaintenance::class);
    }

    public function leaves()
    {
        return $this->hasMany(ProjectLeave::class);
    }

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    public function analysisSessions()
    {
        return $this->hasMany(\App\Models\AnalysisSession::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'on_hold' => 'yellow',
            'completed' => 'blue',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => '진행중',
            'on_hold' => '보류',
            'completed' => '완료',
            'cancelled' => '취소',
            default => '알 수 없음',
        };
    }

    public function isMember(User $user): bool
    {
        return $this->projectMembers()->where('user_id', $user->id)->exists();
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->projectMembers()->where('user_id', $user->id)->first();
        return $member?->role;
    }
}
