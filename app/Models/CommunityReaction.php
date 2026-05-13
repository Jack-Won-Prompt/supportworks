<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityReaction extends Model
{
    protected $fillable = ['post_id', 'user_id', 'emoji'];

    public const EMOJIS = [
        'like'  => '👍',
        'heart' => '❤️',
        'laugh' => '😂',
        'wow'   => '😮',
        'sad'   => '😢',
        'fire'  => '🔥',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }
}
