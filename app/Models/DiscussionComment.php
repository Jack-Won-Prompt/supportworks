<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionComment extends Model
{
    protected $fillable = ['discussion_id', 'user_id', 'content', 'share_token'];

    public function discussion(): BelongsTo  { return $this->belongsTo(Discussion::class); }
    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function attachments(): HasMany   { return $this->hasMany(DiscussionAttachment::class); }
}
