<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityVote extends Model
{
    protected $fillable = ['user_id', 'votable_type', 'votable_id', 'value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
