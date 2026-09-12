<?php

namespace App\Policies\AiWork;

use App\Models\AiWork\AiwJob;
use App\Models\Project;
use App\Models\User;

/**
 * AI Works 권한.
 *
 * 이 기능은 사실상 "작업 PC 에서 명령을 실행할 권한"이다 — 지시 한 줄이 로컬
 * PC 의 소스를 고치고 운영 서버에 배포까지 한다. 그래서 프로젝트 역할과
 * 무관하게 **시스템 관리자에게만** 연다.
 *
 * 프로젝트 멤버 여부는 보지 않는다. 관리자가 멤버가 아닌 프로젝트의 작업 PC 도
 * 다뤄야 하기 때문이다(멤버까지 요구하면 대부분의 프로젝트에서 탭만 보이고
 * 눌리지 않는다).
 *
 * 화면의 탭도 같은 기준으로 숨긴다 — resources/views/partials/project-nav.blade.php.
 */
class AiwJobPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AiwJob $job): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function sendMessage(User $user, AiwJob $job): bool
    {
        return $user->isAdmin();
    }

    public function decidePermission(User $user, AiwJob $job): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, AiwJob $job): bool
    {
        return $user->isAdmin();
    }

    public function end(User $user, AiwJob $job): bool
    {
        return $user->isAdmin();
    }

    public function handover(User $user, AiwJob $job): bool
    {
        return $user->isAdmin();
    }

    /** 작업 PC 등록·토큰 발급. 토큰은 그 PC 를 장악할 수 있는 자격이다. */
    public function manageAgents(User $user): bool
    {
        return $user->isAdmin();
    }
}
