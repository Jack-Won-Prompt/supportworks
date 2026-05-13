<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageImageComment extends Model
{
    protected $fillable = ['message_id', 'user_id', 'admin_user_id', 'admin_name', 'content'];

    public function user()      { return $this->belongsTo(User::class); }
    public function adminUser() { return $this->belongsTo(\App\Models\AdminUser::class); }
    public function message()   { return $this->belongsTo(Message::class); }

    public function displayName(): string
    {
        if ($this->admin_user_id) return $this->admin_name ?? '관리자';
        return $this->user?->name ?? '?';
    }
}
