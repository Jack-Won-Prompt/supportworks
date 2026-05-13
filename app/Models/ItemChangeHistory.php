<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemChangeHistory extends Model
{
    protected $table = 'item_change_histories';
    public $timestamps = false;

    protected $fillable = [
        'item_type', 'item_id', 'changed_by_id',
        'changed_at', 'field_name', 'old_value', 'new_value',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
