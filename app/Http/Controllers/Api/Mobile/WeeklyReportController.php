<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportTask;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyReportController extends Controller
{
    /** GET /weekly-reports - 내 주간 보고 목록 */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $reports = WeeklyReport::with(['project:id,name'])
            ->where('user_id', $user->id)
            ->withCount(['tasks as current_task_count' => fn($q) => $q->where('section', 'current_week')])
            ->withCount(['tasks as next_task_count'    => fn($q) => $q->where('section', 'next_week')])
            ->orderByDesc('week_start_date')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($reports->map(fn($r) => $this->reportResource($r)));
    }

    /** GET /weekly-reports/projects - 내가 보고서 작성 가능한 프로젝트 */
    public function projects(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = Project::whereHas('projectMembers', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($projects);
    }

    /** GET /weekly-reports/{report} */
    public function show(Request $request, WeeklyReport $report): JsonResponse
    {
        abort_if($report->user_id !== $request->user()->id, 403);
        $report->load(['project:id,name', 'tasks']);

        return response()->json([
            ...$this->reportResource($report),
            'summary'        => $report->summary,
            'special_notes'  => $report->special_notes,
            'team_name'      => $report->team_name,
            'author_name'    => $report->author_name,
            'manager_name'   => $report->manager_name,
            'current_tasks'  => $report->tasks->where('section', 'current_week')->values()->map(fn($t) => $this->taskResource($t)),
            'next_tasks'     => $report->tasks->where('section', 'next_week')->values()->map(fn($t) => $this->taskResource($t)),
        ]);
    }

    /** POST /weekly-reports - 새 주간 보고 작성 */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'project_id'      => 'required|exists:projects,id',
            'week_start_date' => 'required|date',
            'team_name'       => 'nullable|string|max:100',
            'summary'         => 'nullable|string',
            'special_notes'   => 'nullable|string',
            'status'          => 'nullable|in:draft,submitted',
            'current_tasks'   => 'array',
            'next_tasks'      => 'array',
        ]);

        $user = $request->user();
        $weekStart = WeeklyReport::getWeekStartDate(Carbon::parse($request->week_start_date));

        $report = DB::transaction(function () use ($request, $user, $weekStart) {
            $report = WeeklyReport::create([
                'project_id'      => $request->project_id,
                'user_id'         => $user->id,
                'company_group_id'=> $user->company_group_id,
                'team_name'       => $request->team_name,
                'author_name'     => $user->name,
                'report_date'     => now()->toDateString(),
                'year'            => (int) $weekStart->isoFormat('GGGG'),
                'week_number'     => (int) $weekStart->isoFormat('WW'),
                'week_start_date' => $weekStart->toDateString(),
                'status'          => $request->status ?? 'draft',
                'summary'         => $request->summary,
                'special_notes'   => $request->special_notes,
            ]);

            $this->syncTasks($report, 'current_week', $request->input('current_tasks', []));
            $this->syncTasks($report, 'next_week', $request->input('next_tasks', []));
            return $report;
        });

        $report->load(['project:id,name', 'tasks']);
        return response()->json($this->reportResource($report), 201);
    }

    /** PUT /weekly-reports/{report} */
    public function update(Request $request, WeeklyReport $report): JsonResponse
    {
        abort_if($report->user_id !== $request->user()->id, 403);

        $request->validate([
            'team_name'     => 'nullable|string|max:100',
            'summary'       => 'nullable|string',
            'special_notes' => 'nullable|string',
            'status'        => 'nullable|in:draft,submitted',
            'current_tasks' => 'array',
            'next_tasks'    => 'array',
        ]);

        DB::transaction(function () use ($request, $report) {
            $report->update($request->only(['team_name', 'summary', 'special_notes', 'status']));
            if ($request->has('current_tasks')) {
                $report->tasks()->where('section', 'current_week')->delete();
                $this->syncTasks($report, 'current_week', $request->input('current_tasks', []));
            }
            if ($request->has('next_tasks')) {
                $report->tasks()->where('section', 'next_week')->delete();
                $this->syncTasks($report, 'next_week', $request->input('next_tasks', []));
            }
        });

        $report->load(['project:id,name', 'tasks']);
        return response()->json($this->reportResource($report));
    }

    /** DELETE /weekly-reports/{report} */
    public function destroy(Request $request, WeeklyReport $report): JsonResponse
    {
        abort_if($report->user_id !== $request->user()->id, 403);
        $report->delete();
        return response()->json(['message' => '주간 보고가 삭제되었습니다.']);
    }

    private function syncTasks(WeeklyReport $report, string $section, array $tasks): void
    {
        foreach (array_values($tasks) as $idx => $t) {
            if (empty($t['task_name'])) continue;
            WeeklyReportTask::create([
                'weekly_report_id' => $report->id,
                'section'          => $section,
                'task_name'        => $t['task_name'],
                'start_date'       => $t['start_date'] ?? null,
                'end_date'         => $t['end_date'] ?? null,
                'status'           => $t['status'] ?? 'pending',
                'sort_order'       => $idx,
            ]);
        }
    }

    private function reportResource(WeeklyReport $r): array
    {
        return [
            'id'                  => $r->id,
            'project'             => $r->project ? ['id' => $r->project->id, 'name' => $r->project->name] : null,
            'week_start_date'     => $r->week_start_date instanceof Carbon ? $r->week_start_date->format('Y-m-d') : (string) $r->week_start_date,
            'week_label'          => $r->week_label,
            'report_date'         => $r->report_date instanceof Carbon ? $r->report_date->format('Y-m-d') : (string) $r->report_date,
            'status'              => $r->status,
            'status_label'        => $r->status === 'submitted' ? '제출' : '작성중',
            'current_task_count'  => $r->current_task_count ?? ($r->tasks ? $r->tasks->where('section', 'current_week')->count() : 0),
            'next_task_count'     => $r->next_task_count ?? ($r->tasks ? $r->tasks->where('section', 'next_week')->count() : 0),
            'updated_at'          => $r->updated_at,
        ];
    }

    private function taskResource(WeeklyReportTask $t): array
    {
        return [
            'id'           => $t->id,
            'task_name'    => $t->task_name,
            'start_date'   => $t->start_date instanceof Carbon ? $t->start_date->format('Y-m-d') : (string) ($t->start_date ?? ''),
            'end_date'     => $t->end_date instanceof Carbon ? $t->end_date->format('Y-m-d') : (string) ($t->end_date ?? ''),
            'status'       => $t->status,
            'status_label' => $t->status_label,
        ];
    }
}