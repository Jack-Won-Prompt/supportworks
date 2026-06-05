<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'translated_body', 'translate_lang', 'file_path', 'file_name', 'file_size', 'reply_to_id', 'edited_at', 'deleted_at'];

    protected $casts = [
        'edited_at'  => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function isImage(): bool
    {
        if (!$this->file_name) return false;
        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $this->file_name);
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function formattedSize(): string
    {
        if (!$this->file_size) return '';
        $kb = $this->file_size / 1024;
        return $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : round($kb, 0) . ' KB';
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->with('sender');
    }

    public function imageComments()
    {
        return $this->hasMany(MessageImageComment::class)->with('user')->orderBy('created_at');
    }
}
