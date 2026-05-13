<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use LogsActivity;
    protected $fillable = ['question_id', 'user_id', 'content', 'is_accepted'];

    protected $casts = [
        'is_accepted' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
