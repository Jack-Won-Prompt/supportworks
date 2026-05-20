<?php

namespace App\Services\WorksBuilder\TaskActions;

use App\Models\User;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskOption;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * 명세 v11 §1.4.2 — 완료 Task 재실행.
 *
 * 원본은 불변 보존. parent_task_id로 신규 Task 분기.
 * 옵션은 원본 current를 복사. 부모의 최종 HTML은 ReopenPromptBuilder에서 컨텍스트로 사용.
 */
class TaskReopenService
{
    public function __construct(private NotificationDispatcher $notifier) {}

    public function reopen(Task $origin, User $user, ?string $note = null): Task
    {
        if (!$origin->isCompleted()) {
            throw new \InvalidArgumentException('완료된 Task만 재실행할 수 있습니다.');
        }

        return DB::transaction(function () use ($origin, $user, $note) {
            $stage = $origin->mode === 'new' ? 'option_input' : 'spec_review';

            $new = Task::create([
                'project_id'          => $origin->project_id,
                'spec_reference_type' => $origin->spec_reference_type,
                'spec_reference_id'   => $origin->spec_reference_id,
                'mode'                => $origin->mode,
                'parent_task_id'      => $origin->id,
                'reopen_reason'       => 'reopen',
                'assignee_id'         => $user->id,
                'current_stage'       => $stage,
                'status'              => Task::STATUS_IN_PROGRESS,
                'started_at'          => now(),
            ]);

            // 원본의 current 옵션 스냅샷 복사
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
                $new, 'reopened', null, null, null,
                "원본 Task #{$origin->id} 재실행" . ($note ? " — {$note}" : ''),
            );

            return $new;
        });
    }
}
