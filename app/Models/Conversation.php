<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['name', 'is_group', 'type', 'status', 'company_group_id', 'assigned_agent_id', 'assigned_admin_id'];

    protected $casts = ['is_group' => 'boolean'];

    public function companyGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function assignedAgent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function assignedAdmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_admin_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function firstMessage()
    {
        return $this->hasOne(Message::class)->oldestOfMany();
    }

    public function unreadCount(int $userId): int
    {
        $pivot    = $this->participants->firstWhere('id', $userId);
        $lastRead = $pivot?->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }

    public function displayName(int $userId): string
    {
        if ($this->is_group) {
            return $this->name ?: '그룹 채팅';
        }
        return $this->otherParticipant($userId)?->name ?? '알수없음';
    }

    public function otherParticipant(int $userId): ?User
    {
        return $this->participants->firstWhere('id', '!=', $userId);
    }

    public function memberNames(int $userId, int $limit = 3): string
    {
        return $this->participants
            ->where('id', '!=', $userId)
            ->take($limit)
            ->pluck('name')
            ->join(', ');
    }

    public static function findBetween(int $userA, int $userB): ?self
    {
        return self::where('is_group', false)
            ->whereNull('type')
            ->whereHas('participants', fn($q) => $q->where('user_id', $userA))
            ->whereHas('participants', fn($q) => $q->where('user_id', $userB))
            ->first();
    }
}
