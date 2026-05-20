<?php

namespace App\Services\WorksBuilder;

use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskOption;
use Illuminate\Support\Facades\DB;

/**
 * 명세 v11 §4.3 — TaskManager.
 *
 * Task 생성, 단계 전이. (재실행/복제/취소는 별도 *Service로 분리.)
 */
class TaskManager
{
    /** 신규 Task 시작 — 시작 폼에서 호출. */
    public function start(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $stage = $data['mode'] === 'new' ? 'option_input' : 'spec_review';

            $task = Task::create([
                'project_id'          => $data['project_id'],
                'spec_reference_type' => $data['spec_reference_type'] ?? null,
                'spec_reference_id'   => $data['spec_reference_id'] ?? null,
                'mode'                => $data['mode'],
                'assignee_id'         => $data['assignee_id'],
                'current_stage'       => $stage,
                'status'              => Task::STATUS_IN_PROGRESS,
                'started_at'          => now(),
            ]);

            if ($task->mode === 'new') {
                TaskOption::create([
                    'task_id'      => $task->id,
                    'options_data' => $this->defaultOptions(),
                    'version'      => 1,
                    'is_current'   => true,
                    'changed_by'   => $data['assignee_id'],
                    'changed_at'   => now(),
                ]);
            }

            return $task;
        });
    }

    public function advanceStage(Task $task, string $stage): Task
    {
        $task->current_stage = $stage;
        $task->save();
        return $task;
    }

    public function complete(Task $task): Task
    {
        $task->current_stage = 'complete';
        $task->status        = Task::STATUS_COMPLETED;
        $task->completed_at  = now();
        $task->save();
        return $task;
    }

    private function defaultOptions(): array
    {
        return [
            'gnb_position'    => 'top',
            'tab_structure'   => 'single',
            'transition_type' => 'page',
            'main_color'      => '#3b82f6',
        ];
    }
}
