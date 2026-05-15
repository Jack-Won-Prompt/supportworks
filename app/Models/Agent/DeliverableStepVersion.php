<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableStepVersion extends Model
{
    protected $table = 'deliverable_step_versions';

    protected $fillable = [
        'deliverable_id',
        'step_order',
        'version_no',
        'snapshot_fields',
        'snapshot_tools',
        'change_note',
        'created_by',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'version_no' => 'integer',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fieldsArray(): array
    {
        $raw = $this->snapshot_fields;
        if (!$raw) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function toolsArray(): array
    {
        $raw = $this->snapshot_tools;
        if (!$raw) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
