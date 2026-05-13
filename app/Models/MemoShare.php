<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoShare extends Model
{
    protected $fillable = ['memo_id', 'shared_by', 'shared_to', 'is_pinned'];

    protected $casts = ['is_pinned' => 'boolean'];

    public function memo()
    {
        return $this->belongsTo(Memo::class);
    }

    public function sharedByUser()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedToUser()
    {
        return $this->belongsTo(User::class, 'shared_to');
    }
}
