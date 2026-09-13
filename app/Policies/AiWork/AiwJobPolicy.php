<?php

namespace App\Policies\AiWork;

use App\Models\AiWork\AiwJob;
use App\Models\Project;
use App\Models\User;

/**
 * AI Works 권한.
 *
 * 이 기능은 사실상 "작업 PC 에서 명령을 실행할 권한"이다 — 지시 한 줄이 로컬
 * PC 의 소스를 고치고 운영 서버에 배포까지 한다. 그래서 프로젝트 역할
 * (manager/member/viewer)만으로는 열지 않는다.
 *
 * 두 가지 길만 열린다.
 *
 *  1. 시스템 관리자 — 항상. 멤버십도 보지 않는다. 관리자가 모든 프로젝트의
 *     멤버는 아니라서, 멤버까지 요구하면 대부분의 프로젝트에서 탭만 보이고
 *     눌리지 않는다.
 *  2. 그 외 사람 — **그 프로젝트의 구성원이면서** 관리자가 '작업 지시 가능'
 *     (users.is_aiw_operator)을 켜 준 경우. 둘 다여야 한다. 플래그만 켜고
 *     남의 프로젝트를 다루게 하지 않고, 구성원이라고 해서 자동으로 열지도
 *     않는다.
 *
 * 화면의 탭도, 실시간 채널 인가도 이 정책을 그대로 호출한다. 조건을 복제하면
 * 한쪽만 옛 규칙으로 남아 "화면은 막혔는데 로그는 흘러가는" 상태가 된다.
 */
class AiwJobPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->allowed($user, $project->id);
    }

    public function view(User $user, AiwJob $job): bool
    {
        return $this->allowed($user, $job->project_id);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->allowed($user, $project->id);
    }

    public function sendMessage(User $user, AiwJob $job): bool
    {
        return $this->allowed($user, $job->project_id);
    }

    public function decidePermission(User $user, AiwJob $job): bool
    {
        return $this->allowed($user, $job->project_id);
    }

    public function cancel(User $user, AiwJob $job): bool
    {
        return $this->allowed($user, $job->project_id);
    }

    public function end(User $user, AiwJob $job): bool
    {
        return $this->allowed($user, $job->project_id);
    }

    public function handover(User $user, AiwJob $job): bool
    {
        return $this->allowed($user, $job->project_id);
    }

    /** 작업 PC 등록·토큰 발급. 토큰은 그 PC 를 장악할 수 있는 자격이라 관리자만. */
    public function manageAgents(User $user): bool
    {
        return $user->isAdmin();
    }

    private function allowed(User $user, ?int $projectId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isAiwOperator() && $this->isMember($user, $projectId);
    }

    private function isMember(User $user, ?int $projectId): bool
    {
        return $projectId !== null
            && $user->projectMembers()->where('project_id', $projectId)->exists();
    }
}
