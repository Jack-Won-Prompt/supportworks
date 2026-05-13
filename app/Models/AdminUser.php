<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class AdminUser extends Authenticatable
{
    protected $fillable = [
        'name', 'login_id', 'email', 'phone', 'password', 'role', 'status',
        'last_login_at', 'login_fail_count', 'locked_until',
        'must_change_pw', 'invited_by', 'accepted_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'last_login_at'   => 'datetime',
        'locked_until'    => 'datetime',
        'accepted_at'     => 'datetime',
        'must_change_pw'  => 'boolean',
        'login_fail_count'=> 'integer',
    ];

    public function companyGroups(): BelongsToMany
    {
        return $this->belongsToMany(CompanyGroup::class, 'admin_company_group_access', 'admin_user_id', 'company_group_id')
                    ->withPivot('can_manage_users', 'can_view_chats')
                    ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Project::class, 'admin_user_project')
                    ->withTimestamps();
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AdminAccessToken::class);
    }

    public function invitedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'invited_by');
    }

    public function isLocked(): bool
    {
        if ($this->status === 'locked' && $this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }
        // 잠금 시간 경과 시 자동 해제
        if ($this->status === 'locked' && $this->locked_until && $this->locked_until->isPast()) {
            $this->update(['status' => 'active', 'login_fail_count' => 0, 'locked_until' => null]);
            return false;
        }
        return false;
    }

    public function getAuthIdentifierName(): string { return 'login_id'; }

    // admin_users 테이블에 remember_token 컬럼 없음 → 비활성화
    public function getRememberTokenName(): string { return ''; }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canAccessGroup(int $groupId): bool
    {
        if ($this->isSuperAdmin()) return true;
        return $this->companyGroups()->where('company_group_id', $groupId)->exists();
    }
}
