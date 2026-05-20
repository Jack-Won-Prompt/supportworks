<?php

namespace App\Services\WorksBuilder\TaskActions;

use App\Models\User;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskOption;
use Illuminate\Support\Facades\DB;

/**
 * 명세 v11 §1.4.1 — 옵션 재입력 (스냅샷 보존).
 *
 * 기존 current row를 is_current=false로, version+1 새 row를 is_current=true로 생성.
 * 이력 추적 가능.
 */
class OptionRevisionService
{
    public function revise(Task $task, array $optionsData, User $user): TaskOption
    {
        if ($task->isImmutable()) {
            throw new \InvalidArgumentException('완료/취소된 Task는 옵션을 변경할 수 없습니다.');
        }

        return DB::transaction(function () use ($task, $optionsData, $user) {
            $current = $task->options()->where('is_current', true)->first();
            $nextVersion = $current ? ($current->version + 1) : 1;

            if ($current) {
                $current->update(['is_current' => false]);
            }

            return TaskOption::create([
                'task_id'      => $task->id,
                'options_data' => $optionsData,
                'version'      => $nextVersion,
                'is_current'   => true,
                'changed_by'   => $user->id,
                'changed_at'   => now(),
            ]);
        });
    }
}
