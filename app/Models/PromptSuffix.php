<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptSuffix extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'body',
        'sort_order',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
