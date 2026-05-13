<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionAttachment extends Model
{
    protected $fillable = ['discussion_id', 'discussion_comment_id', 'user_id', 'original_name', 'path', 'mime_type', 'size'];

    public function discussion(): BelongsTo  { return $this->belongsTo(Discussion::class); }
    public function comment(): BelongsTo     { return $this->belongsTo(DiscussionComment::class, 'discussion_comment_id'); }
    public function uploader(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }

    public function formattedSize(): string
    {
        $b = (int) $this->size;
        if ($b < 1024) return $b . ' B';
        if ($b < 1024 * 1024) return number_format($b / 1024, 1) . ' KB';
        if ($b < 1024 * 1024 * 1024) return number_format($b / 1024 / 1024, 1) . ' MB';
        return number_format($b / 1024 / 1024 / 1024, 2) . ' GB';
    }
}
