<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use LogsActivity;
    protected $fillable = ['user_id', 'title', 'content', 'color', 'is_pinned'];

    protected $casts = ['is_pinned' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shares()
    {
        return $this->hasMany(MemoShare::class);
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'memo_shares', 'memo_id', 'shared_to')
                    ->withPivot('shared_by', 'created_at')
                    ->withTimestamps();
    }
}
