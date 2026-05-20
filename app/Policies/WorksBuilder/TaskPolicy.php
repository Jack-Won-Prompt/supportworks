<?php

namespace App\Policies\WorksBuilder;

use App\Models\User;
use App\Models\WorksBuilder\Task;

/**
 * 명세 v11 §1.4.5 — Task 권한.
 *
 * 허용: 본인 / 같은 프로젝트 멤버 / 관리자.
 * 수정·취소: 본인 / 관리자.
 */
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin()) return true;
        if ($task->assignee_id === $user->id) return true;
        return $task->project
            && $task->project->projectMembers()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->isImmutable()) return false;
        if ($user->isAdmin()) return true;
        return $task->assignee_id === $user->id;
    }

    public function cancel(User $user, Task $task): bool
    {
        if ($task->isImmutable()) return false;
        if ($user->isAdmin()) return true;
        return $task->assignee_id === $user->id;
    }

    public function reopen(User $user, Task $task): bool
    {
        if (!$task->isCompleted()) return false;
        return $this->view($user, $task);
    }

    public function clone(User $user, Task $task): bool
    {
        return $this->reopen($user, $task);
    }
}
