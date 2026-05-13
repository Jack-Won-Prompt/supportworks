<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingAttendee extends Model
{
    protected $fillable = ['minute_id', 'user_id', 'name'];

    public function minute()
    {
        return $this->belongsTo(MeetingMinute::class, 'minute_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
