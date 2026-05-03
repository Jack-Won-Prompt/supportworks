<?php

namespace App\Models\Agent;

use App\Enums\Agent\FrontendStack;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectAiAgentConfig extends Model
{
    protected $table = 'ai_agent_project_configs';

    protected $fillable = [
        'project_id',
        'frontend_stack',
        'backend_stack',
        'ai_agent_enabled',
        'created_by',
    ];

    protected $casts = [
        'frontend_stack'   => FrontendStack::class,
        'ai_agent_enabled' => 'boolean',
    ];

    // project_id는 UNIQUE이므로 단순 ID 참조만 유지 (도메인 격리)
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(AiAgentProjectStage::class, 'project_id', 'project_id')
            ->orderBy('order');
    }

    public function currentStage(): HasOne
    {
        return $this->hasOne(AiAgentProjectStage::class, 'project_id', 'project_id')
            ->whereIn('status', ['in_progress', 'pending_approval'])
            ->orderBy('order')
            ->limit(1);
    }

    // frontend_stack은 최초 설정 후 변경 불가 (도메인 규칙)
    protected function frontendStack(): Attribute
    {
        return Attribute::make(
            set: function (string $value) {
                if ($this->exists && $this->getRawOriginal('frontend_stack') !== null
                    && $this->getRawOriginal('frontend_stack') !== $value) {
                    throw new \DomainException('frontend_stack은 최초 설정 후 변경할 수 없습니다.');
                }
                return $value;
            }
        );
    }

    public static function forProject(int $projectId): ?self
    {
        return static::where('project_id', $projectId)->first();
    }

    public static function initializeForProject(int $projectId, FrontendStack $stack, int $createdBy): self
    {
        $config = static::create([
            'project_id'     => $projectId,
            'frontend_stack' => $stack,
            'created_by'     => $createdBy,
        ]);

        // 5단계 자동 생성
        $stages = [
            ['type' => 'planning',    'name' => '기획',      'order' => 1],
            ['type' => 'design',      'name' => '디자인',    'order' => 2],
            ['type' => 'dev_prep',    'name' => '개발 준비', 'order' => 3],
            ['type' => 'development', 'name' => '개발',      'order' => 4],
            ['type' => 'release',     'name' => '릴리즈',    'order' => 5],
        ];

        foreach ($stages as $i => $stage) {
            AiAgentProjectStage::create([
                'project_id' => $projectId,
                'type'       => $stage['type'],
                'name'       => $stage['name'],
                'status'     => $i === 0 ? 'in_progress' : 'locked',
                'order'      => $stage['order'],
            ]);
        }

        return $config;
    }
}
