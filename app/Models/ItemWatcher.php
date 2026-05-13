<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemWatcher extends Model
{
    protected $table = 'item_watchers';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['item_type', 'item_id', 'user_id', 'subscribed_at'];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
