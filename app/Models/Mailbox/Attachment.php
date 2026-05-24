<?php

namespace App\Models\Mailbox;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;

    protected $table = 'mailbox_attachments';

    protected $fillable = [
        'message_id', 'original_name', 'disk', 'path', 'size', 'mime',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        $b = (int) $this->size;
        if ($b < 1024) return $b . 'B';
        if ($b < 1024 * 1024) return round($b / 1024, 1) . 'KB';
        return round($b / (1024 * 1024), 1) . 'MB';
    }
}
