<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GanttSyncService
{
    /**
     * 프로젝트의 간트 스케줄 목록 반환 (동기화 미리보기용)
     * 각 항목에 sync_status: 'new' | 'existing' | 'updated' 포함
     */
    public function preview(Project $project): array
    {
        $schedules = Schedule::where('project_id', $project->id)
            ->orderBy('sort_order')
            ->orderBy('start_date')
            ->get();

        // gantt_task_id 기준으로 기존 화면 인덱싱
        $existingByGanttId = AiAgentScreen::where('project_id', $project->id)
            ->fromGantt()
            ->get()
            ->keyBy('gantt_task_id');

        $items = $schedules->map(function (Schedule $s) use ($existingByGanttId) {
            $existing = $existingByGanttId->get($s->id);

            if (!$existing) {
                $syncStatus = 'new';
            } elseif ($existing->isArchived()) {
                $syncStatus = 'archived';
            } else {
                $hasChanges = $existing->title !== $s->title
                    || $existing->scheduled_start?->toDateString() !== optional($s->start_date)->toDateString()
                    || $existing->scheduled_end?->toDateString() !== optional($s->end_date)->toDateString()
                    || $existing->assigned_to_user_id !== $s->assigned_to;
                $syncStatus = $hasChanges ? 'updated' : 'existing';
            }

            return [
                'schedule'    => $s,
                'existing'    => $existing,
                'sync_status' => $syncStatus,
            ];
        });

        // 간트에서 사라진 화면 (gantt_task_id는 있지만 스케줄이 없는 것)
        $activeGanttIds = $schedules->pluck('id')->all();
        $orphaned = AiAgentScreen::where('project_id', $project->id)
            ->fromGantt()
            ->active()
            ->whereNotNull('gantt_task_id')
            ->whereNotIn('gantt_task_id', $activeGanttIds ?: [0])
            ->get();

        return [
            'schedules'   => $items,
            'orphaned'    => $orphaned,
            'total'       => $schedules->count(),
            'new_count'   => $items->where('sync_status', 'new')->count(),
            'update_count'=> $items->where('sync_status', 'updated')->count(),
            'orphan_count'=> $orphaned->count(),
        ];
    }

    /**
     * 선택된 스케줄 ID들을 화면으로 동기화 (생성/업데이트)
     * 간트에서 사라진 항목은 아카이브 처리
     *
     * @param  int[]  $selectedScheduleIds
     * @return array{created:int, updated:int, archived:int}
     */
    public function sync(Project $project, array $selectedScheduleIds, int $userId): array
    {
        $created  = 0;
        $updated  = 0;
        $archived = 0;

        if (empty($selectedScheduleIds)) {
            return compact('created', 'updated', 'archived');
        }

        $schedules = Schedule::where('project_id', $project->id)
            ->whereIn('id', $selectedScheduleIds)
            ->get()
            ->keyBy('id');

        $existingByGanttId = AiAgentScreen::where('project_id', $project->id)
            ->fromGantt()
            ->whereIn('gantt_task_id', $selectedScheduleIds)
            ->get()
            ->keyBy('gantt_task_id');

        DB::transaction(function () use (
            $project, $schedules, $existingByGanttId, $userId,
            &$created, &$updated
        ) {
            foreach ($schedules as $schedule) {
                $existing = $existingByGanttId->get($schedule->id);

                if (!$existing) {
                    // 신규 생성
                    $screenId = AiAgentScreen::nextScreenId($project->id);
                    AiAgentScreen::create([
                        'project_id'          => $project->id,
                        'gantt_task_id'       => $schedule->id,
                        'screen_id'           => $screenId,
                        'title'               => $schedule->title,
                        'description'         => $schedule->description,
                        'source'              => 'gantt',
                        'status'              => 'draft',
                        'assigned_to_user_id' => $schedule->assigned_to,
                        'scheduled_start'     => $schedule->start_date?->toDateString(),
                        'scheduled_end'       => $schedule->end_date?->toDateString(),
                        'archived_at'         => null,
                    ]);
                    $created++;
                } else {
                    // 기존 업데이트 (아카이브 상태라면 복원)
                    $existing->update([
                        'title'               => $schedule->title,
                        'description'         => $schedule->description,
                        'assigned_to_user_id' => $schedule->assigned_to,
                        'scheduled_start'     => $schedule->start_date?->toDateString(),
                        'scheduled_end'       => $schedule->end_date?->toDateString(),
                        'archived_at'         => null,
                    ]);
                    $updated++;
                }
            }
        });

        // 선택된 간트 작업 중 현재 프로젝트에서 이미 없는 것들 아카이브
        // (전체 동기화 모드에서만 의미 있음 — 요청 스펙에 따라 선택 동기화는 아카이브 스킵)

        return compact('created', 'updated', 'archived');
    }

    /**
     * 간트에서 삭제된 스케줄에 연결된 화면을 아카이브
     * (전체 동기화 후 orphan 처리 시 별도 호출)
     */
    public function archiveOrphaned(Project $project): int
    {
        $activeGanttIds = Schedule::where('project_id', $project->id)
            ->pluck('id')
            ->all();

        $query = AiAgentScreen::where('project_id', $project->id)
            ->fromGantt()
            ->active()
            ->whereNotNull('gantt_task_id');

        if (!empty($activeGanttIds)) {
            $query->whereNotIn('gantt_task_id', $activeGanttIds);
        }

        $count = $query->count();
        $query->update(['archived_at' => now()]);

        return $count;
    }
}
