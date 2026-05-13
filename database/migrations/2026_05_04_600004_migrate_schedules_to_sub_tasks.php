<?php

/**
 * ⚠️ 실행 전 필수: DB 백업
 *
 * mysqldump -u [user] -p [database] > backup_before_step5_$(date +%Y%m%d).sql
 *
 * 영향 받는 테이블:
 * - schedules (읽기만, 데이터 보존)
 * - milestones (새로 생성)
 * - task_groups (새로 생성)
 * - sub_tasks (새로 생성, 기존 schedules 데이터 이관)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 데이터가 있는 모든 프로젝트 ID 수집
            $projectIds = DB::table('schedules')
                ->distinct()
                ->pluck('project_id');

            foreach ($projectIds as $projectId) {
                $schedules = DB::table('schedules')
                    ->where('project_id', $projectId)
                    ->orderBy('sort_order')
                    ->orderBy('start_date')
                    ->get();

                if ($schedules->isEmpty()) continue;

                // 프로젝트별 group_name 기준으로 그룹화
                $groups = $schedules->groupBy(fn($s) => $s->group_name ?: '기본 그룹');

                // 기본 마일스톤 생성 (마이그레이션용)
                $milestoneId = DB::table('milestones')->insertGetId([
                    'project_id'    => $projectId,
                    'title'         => '기본 마일스톤',
                    'description'   => '기존 일정 데이터 이관 (5단계 마이그레이션)',
                    'status'        => 'planned',
                    'display_order' => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $groupOrder = 0;
                foreach ($groups as $groupName => $groupSchedules) {
                    // 그룹 생성
                    $groupId = DB::table('task_groups')->insertGetId([
                        'project_id'    => $projectId,
                        'milestone_id'  => $milestoneId,
                        'title'         => $groupName,
                        'description'   => '기존 일정 데이터 이관',
                        'display_order' => $groupOrder++,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    // 각 일정 → SubTask 변환
                    foreach ($groupSchedules->values() as $idx => $schedule) {
                        $startDate = $schedule->start_date
                            ? substr($schedule->start_date, 0, 10)
                            : now()->toDateString();

                        $endDate = $schedule->end_date
                            ? substr($schedule->end_date, 0, 10)
                            : now()->addDays(2)->toDateString();

                        // end_date < start_date 보정
                        if ($endDate < $startDate) {
                            $endDate = $startDate;
                        }

                        DB::table('sub_tasks')->insert([
                            'project_id'    => $projectId,
                            'task_group_id' => $groupId,
                            'title'         => $schedule->title,
                            'description'   => $schedule->description,
                            'start_date'    => $startDate,
                            'end_date'      => $endDate,
                            'assignee_id'   => $schedule->assigned_to,
                            'status'        => $this->mapStatus($schedule->status),
                            'progress'      => $this->mapProgress($schedule->status),
                            'display_order' => $idx,
                            'source_type'   => 'migrated',
                            'source_plan_id'=> null,
                            'created_at'    => $schedule->created_at ?? now(),
                            'updated_at'    => $schedule->updated_at ?? now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // 마이그레이션으로 생성된 sub_tasks 삭제 (source_type = 'migrated')
        DB::table('sub_tasks')->where('source_type', 'migrated')->delete();
        // 마이그레이션으로 생성된 task_groups 삭제 (description으로 식별)
        DB::table('task_groups')->where('description', '기존 일정 데이터 이관')->delete();
        // 마이그레이션으로 생성된 milestones 삭제 (description으로 식별)
        DB::table('milestones')->where('description', 'like', '%5단계 마이그레이션%')->delete();
    }

    private function mapStatus(string $oldStatus): string
    {
        return match($oldStatus) {
            'in_progress'      => 'in_progress',
            'completed'        => 'completed',
            'review_completed' => 'completed',
            'review_submitted' => 'in_progress',
            'cancelled'        => 'blocked',
            default            => 'not_started',
        };
    }

    private function mapProgress(string $oldStatus): int
    {
        return match($oldStatus) {
            'completed'        => 100,
            'review_completed' => 90,
            'review_submitted' => 70,
            'in_progress'      => 50,
            default            => 0,
        };
    }
};
