<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableToolResult extends Model
{
    protected $table = 'deliverable_tool_results';

    protected $fillable = [
        'deliverable_id',
        'step_order',
        'tool_id',
        'result',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }
}
