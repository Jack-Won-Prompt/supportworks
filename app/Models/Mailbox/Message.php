<?php

namespace App\Models\Mailbox;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $table = 'mailbox_messages';

    protected $fillable = [
        'thread_id', 'sender_id', 'subject', 'body_html', 'body_text',
        'message_id', 'in_reply_to', 'references_chain',
        'has_attachment', 'recipient_count', 'sent_at',
    ];

    protected $casts = [
        'has_attachment'    => 'boolean',
        'recipient_count'   => 'integer',
        'sent_at'           => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class, 'message_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'message_id');
    }

    /**
     * 스레드 (같은 thread_id 의 모든 메시지, 오래된 순)
     */
    public function threadMessages()
    {
        return static::where('thread_id', $this->thread_id ?: $this->id)
            ->orderBy('sent_at')
            ->orderBy('id');
    }
}
