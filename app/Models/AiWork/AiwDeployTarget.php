<?php

namespace App\Models\AiWork;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 배포 대상. 관리자가 등록한 것만 실행할 수 있다.
 *
 * 명령이 요청에서 오지 않는다는 점이 이 기능의 안전장치 전부다. 화면은 "어느
 * 대상을 실행할지"만 고르고, 무엇이 실행되는지는 이 행이 정한다.
 */
class AiwDeployTarget extends Model
{
    protected $fillable = [
        'project_id', 'name', 'working_dir', 'command', 'runs_on', 'timeout_sec', 'enabled', 'created_by',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        'timeout_sec' => 'integer',
    ];

    /**
     * 담당자 PC 가 실행하는 대상인가.
     *
     * 운영 서버가 이 서버와 다른 프로젝트를 위해 있다. 그 경우 이 서버에는
     * 작업 폴더 자체가 없어 늘 실패한다 — Unicorn Project 가 그랬다.
     */
    public function runsOnAgent(): bool
    {
        return $this->runs_on === 'agent';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deploys(): HasMany
    {
        return $this->hasMany(AiwDeploy::class, 'target_id');
    }

    /** 실행 중인 배포가 있는가. 배포 중 배포는 저장소를 망가뜨린다. */
    public function isBusy(): bool
    {
        return $this->deploys()->whereIn('status', ['queued', 'running'])->exists();
    }

    /** 실수로 누르는 것을 막기 위해 사용자가 그대로 입력해야 하는 문구. */
    public function confirmPhrase(): string
    {
        return $this->name;
    }
}
