<?php

namespace App\Models\PromptBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependency extends Model
{
    protected $table = 'pb_dependencies';

    protected $fillable = [
        'from_builder_id', 'to_builder_id', 'dependency_type',
        'strength', 'auto_detected', 'confidence',
    ];

    protected $casts = [
        'auto_detected' => 'boolean',
        'confidence' => 'float',
    ];

    public function fromBuilder(): BelongsTo
    {
        return $this->belongsTo(Builder::class, 'from_builder_id');
    }

    public function toBuilder(): BelongsTo
    {
        return $this->belongsTo(Builder::class, 'to_builder_id');
    }
}
