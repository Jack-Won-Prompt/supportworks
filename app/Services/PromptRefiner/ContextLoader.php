<?php

namespace App\Services\PromptRefiner;

use App\Models\Project;
use App\Models\PromptHistory;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Str;

class ContextLoader
{
    public function load(?int $projectId, ?int $scheduleId, User $user): ?array
    {
        if (!$projectId) {
            return null;
        }

        $project = Project::find($projectId);
        if (!$project) {
            return null;
        }

        $hasAccess = $user->isAdmin()
            || $project->created_by === $user->id
            || $project->projectMembers()->where('user_id', $user->id)->exists();

        if (!$hasAccess) {
            return null;
        }

        // 일정(Schedule) 모드 — 강한 컨텍스트
        if ($scheduleId) {
            $schedule = Schedule::where('id', $scheduleId)
                ->where('project_id', $projectId)
                ->first();

            if (!$schedule) {
                abort(400, 'INVALID_SCHEDULE_FOR_PROJECT');
            }

            $histories = PromptHistory::where('schedule_id', $scheduleId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            return [
                'context_strength'   => 'task',
                'project_id'         => $project->id,
                'project_name'       => $project->name,
                'task_id'            => $schedule->id,
                'task_name'          => $schedule->title,
                'task_description'   => $schedule->description,
                'tech_stack'         => [],
                'conventions'        => '',
                'previous_prompts'   => $histories->map(fn($h) => [
                    'timestamp'      => $h->created_at->toIso8601String(),
                    'task_type'      => $h->task_type,
                    'user_input'     => $h->original_input,
                    'refined_prompt' => Str::limit($h->refined_prompt, 800),
                ])->toArray(),
            ];
        }

        // 프로젝트 모드 — 중간 컨텍스트
        $histories = PromptHistory::where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'context_strength' => 'project',
            'project_id'       => $project->id,
            'project_name'     => $project->name,
            'tech_stack'       => [],
            'conventions'      => '',
            'previous_prompts' => $histories->map(fn($h) => [
                'timestamp'      => $h->created_at->toIso8601String(),
                'task_type'      => $h->task_type,
                'user_input'     => $h->original_input,
                'refined_prompt' => Str::limit($h->refined_prompt, 400),
            ])->toArray(),
        ];
    }

    public function getProjectTasks(int $projectId, User $user): array
    {
        $project = Project::find($projectId);
        if (!$project) {
            return [];
        }

        $hasAccess = $user->isAdmin()
            || $project->created_by === $user->id
            || $project->projectMembers()->where('user_id', $user->id)->exists();

        if (!$hasAccess) {
            return [];
        }

        $schedules = Schedule::where('project_id', $projectId)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get(['id', 'title', 'status']);

        return $schedules->map(function ($schedule) {
            $count = PromptHistory::where('schedule_id', $schedule->id)->count();
            $last  = PromptHistory::where('schedule_id', $schedule->id)
                ->orderByDesc('created_at')
                ->value('created_at');

            return [
                'schedule_id'      => $schedule->id,
                'title'            => $schedule->title,
                'status'           => $schedule->status,
                'refinement_count' => $count,
                'last_refined_at'  => $last,
            ];
        })->toArray();
    }
}
