<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollabSession extends Model
{
    protected $fillable = [
        'session_key', 'initiator_id', 'participant_id', 'status', 'permission', 'current_url',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function participant()
    {
        return $this->belongsTo(User::class, 'participant_id');
    }
}
