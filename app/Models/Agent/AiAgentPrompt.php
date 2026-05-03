<?php

namespace App\Models\Agent;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentPrompt extends Model
{
    protected $table = 'ai_agent_prompts';

    protected $fillable = [
        'project_id',
        'stage',
        'task_type',
        'name',
        'template',
        'variables',
        'version',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStage($query, string $stage)
    {
        return $query->where('stage', $stage)->orWhere('stage', 'common');
    }

    public function scopeForTask($query, string $taskType)
    {
        return $query->where('task_type', $taskType);
    }

    // {variable} 플레이스홀더를 실제 값으로 교체
    public function render(array $values): string
    {
        $template = $this->template;
        foreach ($values as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    // 활성 프롬프트 중 특정 단계·태스크에 맞는 것 조회 (프로젝트 전용 → 공통 순서)
    public static function resolve(string $stage, string $taskType, ?int $projectId = null): ?self
    {
        return static::active()
            ->forTask($taskType)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId)
                ->orWhereNull('project_id'),
                fn($q) => $q->whereNull('project_id')
            )
            ->orderByDesc('project_id')
            ->first();
    }
}
