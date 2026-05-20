<?php

namespace App\Services\WorksPrompt;

use App\Models\PlanningDoc;
use App\Models\Project;
use App\Models\PromptHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 웍스 프롬프트 컨텍스트 로더.
 *
 * 적재 항목:
 *   1. 최신 PlanningDoc (요약 + 본문 발췌)
 *   2. 동일 프로젝트의 직전 PromptHistory 최대 5건 (이전 질의/응답)
 *   3. 프로젝트 네비게이션 메뉴 데이터 8종 (요약 카운트 + 최근 항목 5건)
 *      - requirements, issues, schedules, questions, files,
 *        members, milestones, analysis_sessions
 *
 * 각 메뉴 조회는 try/catch로 감싸 일부 실패해도 답변은 진행되게 한다.
 */
class PlanContextLoader
{
    private const PLAN_CONTENT_LIMIT = 8000;
    private const PLAN_SUMMARY_LIMIT = 2000;
    private const RECENT_LIMIT       = 5;

    public function load(int $projectId, User $user): ?array
    {
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

        $plan = $project->planningDocs()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        $planArr = null;
        if ($plan instanceof PlanningDoc) {
            $planArr = [
                'title'   => $plan->title,
                'summary' => Str::limit((string)($plan->ai_summary ?? $plan->description ?? ''), self::PLAN_SUMMARY_LIMIT, '...'),
                'content' => Str::limit((string)($plan->content ?? ''), self::PLAN_CONTENT_LIMIT, '...'),
                'version' => (int)($plan->version ?? 1),
                'status'  => (string)($plan->status ?? 'draft'),
            ];
        }

        $histories = PromptHistory::where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'project_id'       => $project->id,
            'project_name'     => $project->name,
            'planning_doc'     => $planArr,
            'previous_prompts' => $histories->map(fn($h) => [
                'timestamp'      => $h->created_at?->toIso8601String(),
                'task_type'      => $h->task_type,
                'user_input'     => Str::limit($h->original_input, 300),
                'refined_prompt' => Str::limit($h->refined_prompt, 400),
            ])->toArray(),
            'navigation_data'  => $this->loadNavigationData($project),
        ];
    }

    /**
     * 프로젝트 네비게이션 메뉴별 lightweight 요약.
     * 각 항목은 try/catch — 일부 메뉴가 실패해도 나머지는 그대로 반환.
     */
    private function loadNavigationData(Project $project): array
    {
        return array_filter([
            'requirements'      => $this->safe(fn() => $this->summarizeRequirements($project)),
            'issues'            => $this->safe(fn() => $this->summarizeIssues($project)),
            'schedules'         => $this->safe(fn() => $this->summarizeSchedules($project)),
            'questions'         => $this->safe(fn() => $this->summarizeQuestions($project)),
            'files'             => $this->safe(fn() => $this->summarizeFiles($project)),
            'members'           => $this->safe(fn() => $this->summarizeMembers($project)),
            'milestones'        => $this->safe(fn() => $this->summarizeMilestones($project)),
            'analysis_sessions' => $this->safe(fn() => $this->summarizeAnalysisSessions($project)),
        ], fn($v) => $v !== null);
    }

    private function safe(callable $fn): ?array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('PlanContextLoader: nav summary failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function summarizeRequirements(Project $project): array
    {
        $byStatus = $project->requirements()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')->toArray();

        $recent = $project->requirements()
            ->select(['id', 'title', 'status', 'priority', 'category'])
            ->latest()->limit(self::RECENT_LIMIT)->get()->toArray();

        return [
            'total'     => (int) array_sum($byStatus),
            'by_status' => $byStatus,
            'recent'    => $recent,
        ];
    }

    private function summarizeIssues(Project $project): array
    {
        $byStatus = $project->issues()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')->toArray();

        $byPriority = $project->issues()
            ->selectRaw('priority, COUNT(*) as cnt')
            ->groupBy('priority')
            ->pluck('cnt', 'priority')->toArray();

        $recent = $project->issues()
            ->select(['id', 'title', 'status', 'priority', 'category'])
            ->latest()->limit(self::RECENT_LIMIT)->get()->toArray();

        return [
            'total'       => (int) array_sum($byStatus),
            'by_status'   => $byStatus,
            'by_priority' => $byPriority,
            'recent'      => $recent,
        ];
    }

    private function summarizeSchedules(Project $project): array
    {
        $byStatus = $project->schedules()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')->toArray();

        $upcoming = $project->schedules()
            ->select(['id', 'title', 'status', 'priority', 'start_date', 'end_date'])
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit(self::RECENT_LIMIT)->get()->toArray();

        return [
            'total'     => (int) array_sum($byStatus),
            'by_status' => $byStatus,
            'upcoming'  => $upcoming,
        ];
    }

    private function summarizeQuestions(Project $project): array
    {
        $byStatus = $project->questions()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')->toArray();

        $recent = $project->questions()
            ->select(['id', 'title', 'status', 'is_private', 'created_at'])
            ->latest()->limit(self::RECENT_LIMIT)->get()->toArray();

        return [
            'total'     => (int) array_sum($byStatus),
            'by_status' => $byStatus,
            'recent'    => $recent,
        ];
    }

    private function summarizeFiles(Project $project): array
    {
        $total = $project->files()->count();
        $recent = $project->files()
            ->select(['id', 'original_name', 'file_type', 'size', 'created_at'])
            ->latest()->limit(self::RECENT_LIMIT)->get()->toArray();

        return [
            'total'  => (int) $total,
            'recent' => $recent,
        ];
    }

    private function summarizeMembers(Project $project): array
    {
        $members = $project->projectMembers()
            ->with(['user:id,name,email,role'])
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'user_id' => $m->user_id,
                'name'    => $m->user?->name,
                'email'   => $m->user?->email,
                'role'    => $m->role ?? $m->user?->role,
            ])->toArray();

        return [
            'total' => count($members),
            'list'  => $members,
        ];
    }

    private function summarizeMilestones(Project $project): array
    {
        $list = $project->milestones()
            ->select(['id', 'title', 'status', 'target_date', 'display_order'])
            ->orderBy('display_order')
            ->orderBy('target_date')
            ->limit(10)
            ->get()->toArray();

        return [
            'total' => $project->milestones()->count(),
            'list'  => $list,
        ];
    }

    private function summarizeAnalysisSessions(Project $project): array
    {
        $sessions = $project->analysisSessions()
            ->select(['id', 'status', 'llm_provider', 'llm_model', 'created_at'])
            ->latest()->limit(self::RECENT_LIMIT)->get()->toArray();

        return [
            'total'  => $project->analysisSessions()->count(),
            'recent' => $sessions,
        ];
    }
}
