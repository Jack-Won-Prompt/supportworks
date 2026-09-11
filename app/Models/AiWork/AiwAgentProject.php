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

    protected $fillable = ['agent_id', 'project_id', 'display_name', 'local_path', 'default_branch'];

    /** 이 프로젝트에서 부를 이름. 비워 두면 담당자 본래 이름. */
    public function displayName(): string
    {
        return $this->display_name ?: ($this->agent?->name ?? '담당자');
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
