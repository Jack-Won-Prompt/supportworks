<?php

namespace App\Services\WorksBuilder\TaskActions;

use App\Models\User;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskOption;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * 명세 v11 §1.4.3 — 완료 Task 복제.
 *
 * 옵션만 복사, HTML은 사용하지 않음. 새 화면을 만든다는 의도.
 */
class TaskCloneService
{
    public function __construct(private NotificationDispatcher $notifier) {}

    public function clone(Task $origin, User $user): Task
    {
        if (!$origin->isCompleted()) {
            throw new \InvalidArgumentException('완료된 Task만 복제할 수 있습니다.');
        }

        return DB::transaction(function () use ($origin, $user) {
            $stage = $origin->mode === 'new' ? 'option_input' : 'spec_review';

            $new = Task::create([
                'project_id'          => $origin->project_id,
                'spec_reference_type' => $origin->spec_reference_type,
                'spec_reference_id'   => $origin->spec_reference_id,
                'mode'                => $origin->mode,
                'parent_task_id'      => $origin->id,
                'reopen_reason'       => 'clone',
                'assignee_id'         => $user->id,
                'current_stage'       => $stage,
                'status'              => Task::STATUS_IN_PROGRESS,
                'started_at'          => now(),
            ]);

            $originCurrent = $origin->options()->where('is_current', true)->first();
            if ($originCurrent) {
                TaskOption::create([
                    'task_id'      => $new->id,
                    'options_data' => $originCurrent->options_data,
                    'version'      => 1,
                    'is_current'   => true,
                    'changed_by'   => $user->id,
                    'changed_at'   => now(),
                ]);
            }

            $this->notifier->dispatchStage(
                $new, 'cloned', null, null, null,
                "원본 Task #{$origin->id} 복제",
            );

            return $new;
        });
    }
}
