<?php

namespace App\Models;

use App\Models\Agent\AiAgentUserCredential;
use Database\Factories\UserFactory;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_sr_agent', 'company', 'phone', 'avatar', 'agent_status', 'company_group_id', 'is_guest',
    ];

    protected $attributes = [
        'is_guest'    => false,
        'is_sr_agent' => false,
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_sr_agent' => 'boolean',
        ];
    }

    public function companyGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMember(): bool
    {
        return in_array($this->role, ['admin', 'member']);
    }

    // ── SR 담당자 여부 (관리자 사용자 관리에서 지정) ─────────────────────
    public function isSrAgent(): bool
    {
        return (bool) $this->is_sr_agent;
    }

    public function hasFeature(string $key): bool
    {
        return $this->companyGroup?->hasFeature($key) ?? true;
    }

    // ── 회사 소속 여부 ────────────────────────────────────────────────────
    public function hasCompany(): bool
    {
        return !is_null($this->company_group_id);
    }

    // ── 동일 회사 여부 검사 ───────────────────────────────────────────────
    public function inSameCompany(self $other): bool
    {
        return $this->company_group_id !== null
            && $this->company_group_id === $other->company_group_id;
    }

    // ── 같은 회사 구성원 ID 목록 (본인 포함) ─────────────────────────────
    public function companyMemberIds(): array
    {
        if (!$this->company_group_id) {
            return [$this->id];
        }
        return static::where('company_group_id', $this->company_group_id)
            ->pluck('id')
            ->all();
    }

    // ── 로컬 스코프: 특정 사용자와 같은 회사 구성원 ─────────────────────
    public function scopeCompanyOf(Builder $query, self $user): Builder
    {
        if ($user->company_group_id) {
            return $query->where('company_group_id', $user->company_group_id);
        }
        return $query->where('id', $user->id);
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function aiAgentCredential(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AiAgentUserCredential::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'admin' => 'red',
            'member' => 'blue',
            default => 'green',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => '관리자',
            'member' => '멤버',
            default => '외부사용자',
        };
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
