<?php

namespace App\Services\Schedule;

use App\Models\Milestone;
use App\Models\SubTask;
use App\Models\TaskGroup;
use Illuminate\Support\Collection;

class ScheduleService
{
    /**
     * Return full tree: Milestone → TaskGroup → SubTask for a project.
     * Also returns ungrouped sub_tasks (task_group_id references deleted group).
     */
    public function getTree(int $projectId): array
    {
        $milestones = Milestone::where('project_id', $projectId)
            ->orderBy('display_order')
            ->with([
                'taskGroups' => fn($q) => $q->orderBy('display_order'),
                'taskGroups.subTasks' => fn($q) => $q->orderBy('display_order')->with(['assignee', 'files']),
            ])
            ->get();

        return [
            'milestones' => $milestones,
            'ungrouped'  => $this->getUngrouped($projectId, $milestones),
            'loose'      => $this->getLoose($projectId),
        ];
    }

    /**
     * Return sub_tasks that belong to task_groups not attached to any milestone.
     */
    private function getUngrouped(int $projectId, Collection $milestones): Collection
    {
        $groupedIds = $milestones->flatMap(fn($m) => $m->taskGroups->pluck('id'));

        return TaskGroup::where('project_id', $projectId)
            ->whereNull('milestone_id')
            ->orderBy('display_order')
            ->with([
                'subTasks' => fn($q) => $q->orderBy('display_order')->with(['assignee', 'files']),
            ])
            ->whereNotIn('id', $groupedIds)
            ->get();
    }

    /**
     * SubTasks with no TaskGroup at all (task_group_id = NULL).
     */
    private function getLoose(int $projectId): Collection
    {
        return SubTask::where('project_id', $projectId)
            ->whereNull('task_group_id')
            ->orderBy('display_order')
            ->with(['assignee', 'files'])
            ->get();
    }

    /**
     * Build flat gantt rows from SubTask for a given project.
     */
    public function ganttRows(int $projectId): array
    {
        $subTasks = SubTask::where('project_id', $projectId)
            ->with(['taskGroup', 'assignee'])
            ->orderBy('task_group_id')
            ->orderBy('display_order')
            ->get();

        return $subTasks->map(fn(SubTask $t) => [
            'id'           => $t->id,
            'name'         => $t->title,
            'group_name'   => $t->taskGroup?->title ?? '미분류',
            'start'        => $t->start_date?->format('Y-m-d') ?? now()->toDateString(),
            'end'          => $t->end_date?->format('Y-m-d') ?? now()->toDateString(),
            'progress'     => $t->progress,
            '_status'      => $t->status,
            '_status_label'=> $t->status_label,
            '_assignee'    => $t->assignee?->name,
            '_description' => $t->description,
            '_start_dt'    => $t->start_date?->format('Y-m-d'),
            '_end_dt'      => $t->end_date?->format('Y-m-d'),
            '_source_type' => $t->source_type,
        ])->values()->all();
    }

    public function reorderSubTasks(array $orderedIds): void
    {
        foreach ($orderedIds as $idx => $id) {
            SubTask::where('id', $id)->update(['display_order' => $idx]);
        }
    }

    public function reorderTaskGroups(array $orderedIds): void
    {
        foreach ($orderedIds as $idx => $id) {
            TaskGroup::where('id', $id)->update(['display_order' => $idx]);
        }
    }

    public function reorderMilestones(array $orderedIds): void
    {
        foreach ($orderedIds as $idx => $id) {
            Milestone::where('id', $id)->update(['display_order' => $idx]);
        }
    }
}
