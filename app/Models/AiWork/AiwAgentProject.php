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

    protected $fillable = ['agent_id', 'project_id', 'local_path', 'default_branch'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiwAgent::class, 'agent_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
