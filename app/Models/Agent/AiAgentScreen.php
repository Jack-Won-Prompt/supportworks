<?php

namespace App\Models\Agent;

use App\Enums\Agent\FrontendStack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentScreen extends Model
{
    protected $table = 'ai_agent_screens';

    protected $fillable = [
        'project_id',
        'artifact_id',
        'screen_id',
        'title',
        'description',
        'figma_url',
        'figma_frame_id',
        'figma_dev_mode_url',
        'generation_prompt',
        'mockup_content',
        'stack',
        'status',
        'order',
    ];

    protected $casts = [
        'stack' => FrontendStack::class,
    ];

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function traceabilityLinks(): HasMany
    {
        return $this->hasMany(AiAgentTraceabilityLink::class, 'source_id')
            ->where('source_type', 'screen');
    }

    public function hasFigmaMapping(): bool
    {
        return !empty($this->figma_frame_id);
    }

    public function hasDevModeUrl(): bool
    {
        return !empty($this->figma_dev_mode_url);
    }

    // SCR-001 형식으로 다음 순번 자동 생성
    public static function nextScreenId(int $projectId): string
    {
        $max = static::where('project_id', $projectId)
            ->orderByDesc('screen_id')
            ->value('screen_id');

        if (!$max) {
            return 'SCR-001';
        }

        $num = (int) substr($max, 4) + 1;
        return 'SCR-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
