<?php

namespace App\Models\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\FrontendStack;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AiAgentScreen extends Model
{
    protected $table = 'ai_agent_screens';

    protected $fillable = [
        'project_id',
        'artifact_id',
        'gantt_task_id',
        'screen_id',
        'title',
        'description',
        'figma_url',
        'figma_frame_id',
        'figma_dev_mode_url',
        'figma_file_key',
        'figma_frame_name',
        'figma_mapped_at',
        'figma_mapped_by',
        'generation_prompt',
        'mockup_content',
        'stack',
        'status',
        'order',
        'source',
        'assigned_to_user_id',
        'scheduled_start',
        'scheduled_end',
        'archived_at',
    ];

    protected $casts = [
        'stack'           => FrontendStack::class,
        'scheduled_start' => 'date',
        'scheduled_end'   => 'date',
        'archived_at'     => 'datetime',
        'figma_mapped_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function ganttTask(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'gantt_task_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function traceabilityLinks(): HasMany
    {
        return $this->hasMany(AiAgentTraceabilityLink::class, 'source_id')
            ->where('source_type', 'screen');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeFromGantt(Builder $query): Builder
    {
        return $query->where('source', 'gantt');
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('source', 'manual');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isFromGantt(): bool
    {
        return $this->source === 'gantt';
    }

    public function archive(): bool
    {
        return $this->update(['archived_at' => now()]);
    }

    public function restore(): bool
    {
        return $this->update(['archived_at' => null]);
    }

    public function hasFigmaMapping(): bool
    {
        return !empty($this->figma_frame_id);
    }

    public function isMappedToFigma(): bool
    {
        return $this->hasFigmaMapping();
    }

    public function hasDevModeUrl(): bool
    {
        return !empty($this->figma_dev_mode_url);
    }

    public function mapToFigma(string $fileKey, string $nodeId, string $frameName, int $userId): void
    {
        $encodedNodeId   = urlencode($nodeId);
        $figmaUrl        = "https://www.figma.com/file/{$fileKey}/?node-id={$encodedNodeId}";
        $figmaDevModeUrl = "https://www.figma.com/file/{$fileKey}/?node-id={$encodedNodeId}&mode=dev";

        $this->update([
            'figma_file_key'    => $fileKey,
            'figma_frame_id'    => $nodeId,
            'figma_frame_name'  => $frameName,
            'figma_url'         => $figmaUrl,
            'figma_dev_mode_url' => $figmaDevModeUrl,
            'figma_mapped_at'   => now(),
            'figma_mapped_by'   => $userId,
        ]);
    }

    public function unmapFromFigma(): void
    {
        $this->update([
            'figma_file_key'     => null,
            'figma_frame_id'     => null,
            'figma_frame_name'   => null,
            'figma_url'          => null,
            'figma_dev_mode_url' => null,
            'figma_mapped_at'    => null,
            'figma_mapped_by'    => null,
        ]);
    }

    public function getFigmaViewUrl(): ?string
    {
        if (!$this->hasFigmaMapping()) return null;
        return $this->figma_url;
    }

    public function getFigmaDevModeUrl(): ?string
    {
        if (!$this->hasFigmaMapping()) return null;
        return $this->figma_dev_mode_url;
    }

    /**
     * T30 layout_spec 산출물에서 이 화면에 적용된 표준 레이아웃 역참조
     */
    public function getAppliedLayouts(): array
    {
        if (!$this->hasFigmaMapping()) return [];

        $layoutArtifact = AiAgentArtifact::where('project_id', $this->project_id)
            ->where('type', ArtifactType::LAYOUT_SPEC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        if (!$layoutArtifact || empty($layoutArtifact->content)) return [];

        $data    = is_array($layoutArtifact->content)
            ? $layoutArtifact->content
            : json_decode($layoutArtifact->content, true);

        $applied = [];
        foreach ($data['standard_layouts'] ?? [] as $key => $layout) {
            if (in_array($this->figma_frame_id, $layout['used_in_frames'] ?? [], true)) {
                $applied[] = [
                    'key'  => $key,
                    'name' => $layout['name'],
                    'spec' => $layout['spec'] ?? [],
                ];
            }
        }

        return $applied;
    }

    // ── SCR-XXX ID generation (concurrent-safe) ───────────────────────────

    public static function nextScreenId(int $projectId): string
    {
        return DB::transaction(function () use ($projectId) {
            $max = static::where('project_id', $projectId)
                ->lockForUpdate()
                ->orderByDesc('screen_id')
                ->value('screen_id');

            if (!$max) {
                return 'SCR-001';
            }

            $num = (int) substr($max, 4) + 1;
            return 'SCR-' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });
    }
}
