<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class MeetingMemo extends Model
{
    use LogsActivity;
    protected $fillable = ['minute_id', 'user_id', 'content'];

    public function minute()
    {
        return $this->belongsTo(MeetingMinute::class, 'minute_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionItems()
    {
        return $this->hasMany(MeetingActionItem::class, 'memo_id');
    }
}
