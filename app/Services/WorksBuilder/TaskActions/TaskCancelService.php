<?php

namespace App\Services\WorksBuilder\TaskActions;

use App\Models\User;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * 명세 v11 §1.4.6 — 진행 중 Task 취소.
 */
class TaskCancelService
{
    public function __construct(private NotificationDispatcher $notifier) {}

    public function cancel(Task $task, User $user, ?string $reason = null): Task
    {
        if ($task->isImmutable()) {
            throw new \InvalidArgumentException('완료/취소된 Task는 다시 취소할 수 없습니다.');
        }

        return DB::transaction(function () use ($task, $user, $reason) {
            $task->update([
                'status'        => Task::STATUS_CANCELLED,
                'current_stage' => 'cancelled',
                'cancelled_at'  => now(),
            ]);
            $task->delete(); // soft delete

            $this->notifier->dispatchStage(
                $task, 'cancelled', null, null, null,
                $reason ?: 'Task가 취소되었습니다.',
            );

            return $task;
        });
    }
}
