<?php

namespace App\Policies\AiWork;

use App\Models\AiWork\AiwJob;
use App\Models\Project;
use App\Models\User;

/**
 * AI Works 권한.
 *
 * 이 기능은 사실상 "작업 PC 에서 명령을 실행할 권한"이므로, 조회와 실행을
 * 분리한다. 프로젝트 멤버면 볼 수 있고, 편집 권한(manager/member) 이상이어야
 * 지시를 만들거나 승인·취소할 수 있다. viewer 는 읽기만 한다.
 */
class AiwJobPolicy
{
    /** 지시 등록·메시지·승인·취소가 가능한 역할. */
    private const EDIT_ROLES = ['manager', 'member'];

    public function viewAny(User $user, Project $project): bool
    {
        return $this->isMember($user, $project->id);
    }

    public function view(User $user, AiwJob $job): bool
    {
        return $this->isMember($user, $job->project_id);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->canEdit($user, $project->id);
    }

    public function sendMessage(User $user, AiwJob $job): bool
    {
        return $this->canEdit($user, $job->project_id);
    }

    public function decidePermission(User $user, AiwJob $job): bool
    {
        return $this->canEdit($user, $job->project_id);
    }

    public function cancel(User $user, AiwJob $job): bool
    {
        return $this->canEdit($user, $job->project_id);
    }

    public function end(User $user, AiwJob $job): bool
    {
        return $this->canEdit($user, $job->project_id);
    }

    public function handover(User $user, AiwJob $job): bool
    {
        return $this->canEdit($user, $job->project_id);
    }

    /** 작업 PC 등록·토큰 발급은 관리자만. 토큰은 그 PC 를 장악할 수 있는 자격이다. */
    public function manageAgents(User $user): bool
    {
        return (string) ($user->role ?? '') === 'admin';
    }

    private function isMember(User $user, ?int $projectId): bool
    {
        return $projectId !== null
            && $user->projectMembers()->where('project_id', $projectId)->exists();
    }

    private function canEdit(User $user, ?int $projectId): bool
    {
        return $projectId !== null
            && $user->projectMembers()
                ->where('project_id', $projectId)
                ->whereIn('role', self::EDIT_ROLES)
                ->exists();
    }
}
