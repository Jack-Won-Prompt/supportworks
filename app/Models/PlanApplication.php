<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'requirement_id', 'plan_id', 'applied_by_id', 'applied_at',
        'insertion_position', 'section_anchor', 'template_used', 'inserted_markdown',
        'is_completed', 'completed_at',
    ];

    protected $casts = [
        'applied_at'   => 'datetime',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanningDoc::class, 'plan_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_id');
    }
}
