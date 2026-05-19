<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SrTarget extends Model
{
    protected $fillable = [
        'title', 'project_id', 'created_by', 'company_group_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function maintenances()
    {
        return $this->hasMany(ProjectMaintenance::class);
    }

    public function maintenanceFileCategories()
    {
        return $this->hasMany(MaintenanceFileCategory::class)->orderBy('sort_order');
    }

    /**
     * 사용자가 접근 가능한 SR 대상인지 판단.
     * 관리자 / 생성자 / 같은 회사 / 연결 프로젝트의 멤버면 접근 허용.
     */
    public function isAccessibleBy(User $user): bool
    {
        if ($user->isAdmin() || $user->isSrAgent()) {
            return true;
        }
        if ($this->created_by === $user->id) {
            return true;
        }
        if ($this->company_group_id && $this->company_group_id === $user->company_group_id) {
            return true;
        }
        if ($this->project && $this->project->isMember($user)) {
            return true;
        }
        return false;
    }

    /**
     * 사용자에게 보이는 SR 대상 목록 쿼리.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isSrAgent()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id);

            if ($user->company_group_id) {
                $q->orWhere('company_group_id', $user->company_group_id);
            }

            $projectIds = $user->projects()->pluck('projects.id');
            if ($projectIds->isNotEmpty()) {
                $q->orWhereIn('project_id', $projectIds);
            }
        });
    }
}
