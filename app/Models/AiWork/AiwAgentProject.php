<?php

namespace App\Models\AiWork;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 작업 PC ↔ 프로젝트 매핑. local_path 가 데몬의 작업 루트(샌드박스 ROOT)가 된다. */
class AiwAgentProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 'project_id', 'display_name', 'local_path', 'default_branch', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /** 이 프로젝트에서 부를 이름. 비워 두면 담당자 본래 이름. */
    public function displayName(): string
    {
        return $this->display_name ?: ($this->agent?->name ?? '담당자');
    }

    /**
     * 이 프로젝트를 맡은 프로세스가 살아 있는가.
     *
     * 프로젝트마다 따로 도는 구조에서는 그중 하나만 죽을 수 있다. 담당자 전체의
     * last_seen_at 을 보면 멈춘 프로젝트가 온라인으로 보인다.
     *
     * 아직 매핑 단위 하트비트를 받은 적이 없으면 담당자 것으로 판단한다 —
     * 프로세스를 나누기 전에 만들어진 매핑은 그 값이 비어 있기 때문이다.
     */
    public function getIsOnlineAttribute(): bool
    {
        $window = now()->subSeconds((int) config('aiw.offline_after_sec', 90));

        if ($this->last_seen_at !== null) {
            return $this->last_seen_at->gt($window);
        }

        return (bool) $this->agent?->is_online;
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiwAgent::class, 'agent_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
