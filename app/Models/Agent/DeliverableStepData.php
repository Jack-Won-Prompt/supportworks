<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableStepData extends Model
{
    protected $table = 'deliverable_step_data';

    protected $fillable = [
        'deliverable_id',
        'step_order',
        'field_key',
        'value',
        'en_value',
        'en_hash',
        'image_map',
    ];

    protected $casts = [
        'image_map' => 'array',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }
}
