<?php

namespace App\Models;

use App\Traits\LogsActivity;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    use LogsActivity;
    use LogsActivity, BelongsToCompany;

    protected $fillable = [
        'user_id', 'company_group_id', 'category', 'title', 'content', 'votes', 'pinned',
    ];

    protected $casts = ['pinned' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id')
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('votes', 'desc')
            ->orderBy('created_at', 'asc');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'question'     => '#3b82f6',
            'idea'         => '#10b981',
            'announcement' => '#ef4444',
            'technical'    => '#8b5cf6',
            default        => '#6b7280',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'question'     => '질문',
            'idea'         => '아이디어',
            'announcement' => '공지',
            'technical'    => '기술',
            default        => '일반',
        };
    }
}
