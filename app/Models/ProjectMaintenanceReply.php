<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMaintenanceReply extends Model
{
    protected $fillable = ['project_maintenance_id', 'user_id', 'admin_user_id', 'content'];

    public function maintenance()
    {
        return $this->belongsTo(ProjectMaintenance::class, 'project_maintenance_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adminUser()
    {
        return $this->belongsTo(\App\Models\AdminUser::class, 'admin_user_id');
    }

    public function authorName(): string
    {
        if ($this->adminUser) return $this->adminUser->name ?? '관리자';
        if ($this->user)      return $this->user->name;
        return '알 수 없음';
    }

    public function isAdminReply(): bool
    {
        return (bool) $this->admin_user_id;
    }
}
