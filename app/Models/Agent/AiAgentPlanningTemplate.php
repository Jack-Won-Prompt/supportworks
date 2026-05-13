<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class AiAgentPlanningTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'version',
        'description',
        'structure',
        'template_path',
        'is_active',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'structure'  => 'array',
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getDefault(): ?static
    {
        return static::where('is_active', true)
            ->where('is_default', true)
            ->latest()
            ->first();
    }

    public static function getActive(): ?static
    {
        return static::where('is_active', true)
            ->latest()
            ->first();
    }

    public function getSections(): array
    {
        return $this->structure['sections'] ?? [];
    }

    public function getVariables(): array
    {
        return $this->structure['variables'] ?? [];
    }

    public function getRequiredData(): array
    {
        return $this->structure['required_data'] ?? [];
    }

    public function getMetadata(): array
    {
        return $this->structure['metadata'] ?? [];
    }

    public function getAiSectionCount(): int
    {
        $count = 0;
        foreach ($this->getSections() as $section) {
            foreach ($section['subsections'] ?? [] as $sub) {
                if (($sub['type'] ?? '') === 'ai_generated') {
                    $count++;
                }
            }
        }
        return $count;
    }
}
