<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deliverable extends Model
{
    protected $table = 'deliverables';

    protected $fillable = [
        'project_id',
        'type_id',
        'current_step',
        'status',
        'responsibility',
        'created_by',
        'share_token',
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    public function stepData(): HasMany
    {
        return $this->hasMany(DeliverableStepData::class);
    }

    public function toolResults(): HasMany
    {
        return $this->hasMany(DeliverableToolResult::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DeliverableApproval::class)->latest();
    }

    public function viewerComments(): HasMany
    {
        return $this->hasMany(DeliverableComment::class);
    }

    public function stepVersions(): HasMany
    {
        return $this->hasMany(DeliverableStepVersion::class);
    }

    public function latestStepVersion(int $step): ?DeliverableStepVersion
    {
        return DeliverableStepVersion::where('deliverable_id', $this->id)
            ->where('step_order', $step)
            ->orderByDesc('version_no')
            ->first();
    }

    public function nextStepVersionNo(int $step): int
    {
        $max = DeliverableStepVersion::where('deliverable_id', $this->id)
            ->where('step_order', $step)
            ->max('version_no');
        return (int) ($max ?? 0) + 1;
    }

    public function getStepValue(int $step, string $fieldKey): mixed
    {
        return $this->stepData
            ->where('step_order', $step)
            ->where('field_key', $fieldKey)
            ->first()
            ?->value;
    }

    public function getStepEnData(int $step, string $fieldKey): array
    {
        $row     = $this->stepData->where('step_order', $step)->where('field_key', $fieldKey)->first();
        $value   = $row?->value   ?? '';
        $enValue = $row?->en_value ?? '';
        $enHash  = $row?->en_hash  ?? '';
        $valid   = $enHash !== '' && $enHash === md5($value);

        return ['en_value' => $enValue, 'valid' => $valid];
    }

    public function getToolResult(int $step, string $toolId): mixed
    {
        $raw = $this->toolResults
            ->where('step_order', $step)
            ->where('tool_id', $toolId)
            ->first()
            ?->result;

        return $raw ? json_decode($raw, true) : null;
    }

    public function getProgressPercent(int $totalSteps): int
    {
        if ($totalSteps <= 0) return 0;
        return (int) round((($this->current_step - 1) / $totalSteps) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
