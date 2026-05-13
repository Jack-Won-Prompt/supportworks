<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageImageAnnotation extends Model
{
    protected $fillable = ['message_id', 'user_id', 'type', 'data'];
    protected $casts    = ['data' => 'array'];

    public function user()    { return $this->belongsTo(User::class); }
    public function message() { return $this->belongsTo(Message::class); }
}
