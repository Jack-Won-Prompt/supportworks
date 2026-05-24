<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Project;
use App\Models\SystemErrorLog;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportTask;
use App\Services\AiOrchestrator;
use App\Services\DocxWriter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WeeklyReportController extends Controller
{
    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$project->isMember($user)) {
            abort(403);
        }
    }

    private function getWeekStart(string $dateStr): Carbon
    {
        return Carbon::parse($dateStr)->startOfWeek(Carbon::MONDAY);
    }

    // ─── 목록 ────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        $isManager = $user->isAdmin() || $project->getMemberRole($user) === 'manager';

        $reports = WeeklyReport::where('project_id', $project->id)
            ->with('user:id,name')
            ->orderByDesc('week_start_date')
            ->orderByDesc('updated_at')
            ->get();

        return view('weekly-reports.index', compact('project', 'reports', 'user', 'isManager'));
    }

    // ─── 삭제 ────────────────────────────────────────────────────────

    public function destroy(Project $project, WeeklyReport $weeklyReport): RedirectResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        if ($weeklyReport->project_id !== $project->id) abort(404);
        if ($weeklyReport->user_id !== $user->id && !$user->isAdmin()) abort(403);

        $weeklyReport->delete();

        return redirect()->route('projects.weekly-reports.index', $project)
            ->with('success', '보고서가 삭제되었습니다.');
    }

    // ─── 작성/수정 폼 ────────────────────────────────────────────────

    public function create(Request $request, Project $project): View|RedirectResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        $dateStr   = $request->get('date', today()->format('Y-m-d'));
        $weekStart = $this->getWeekStart($dateStr);

        $existing = WeeklyReport::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->first();

        if ($existing) {
            return redirect()->route('projects.weekly-reports.edit', [$project, $existing]);
        }

        $teamNames      = $this->getTeamNameList($project);
        $projectMembers = $project->members()->orderBy('name')->get(['users.id', 'users.name']);

        return view('weekly-reports.edit', [
            'project'             => $project,
            'report'              => null,
            'weekStartDate'       => $weekStart->format('Y-m-d'),
            'teamNames'           => $teamNames,
            'projectMembers'      => $projectMembers,
            'initialCurrentTasks' => [],
            'initialNextWeekTasks' => [],
        ]);
    }

    public function edit(Project $project, WeeklyReport $weeklyReport): View
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        if ($weeklyReport->project_id !== $project->id) abort(404);
        if ($weeklyReport->user_id !== $user->id && !$user->isAdmin()) abort(403);

        $weeklyReport->load('tasks');
        $teamNames      = $this->getTeamNameList($project);
        $projectMembers = $project->members()->orderBy('name')->get(['users.id', 'users.name']);

        return view('weekly-reports.edit', [
            'project'             => $project,
            'report'              => $weeklyReport,
            'weekStartDate'       => $weeklyReport->week_start_date->format('Y-m-d'),
            'teamNames'           => $teamNames,
            'projectMembers'      => $projectMembers,
            'initialCurrentTasks' => $this->serializeCurrentTasks($weeklyReport),
            'initialNextWeekTasks' => $this->serializeNextWeekTasks($weeklyReport),
        ]);
    }

    private function serializeCurrentTasks(WeeklyReport $report): array
    {
        return $report->tasks
            ->where('section', 'current_week')
            ->map(fn ($t) => [
                'task_name'     => $t->task_name,
                'start_date'    => $t->start_date?->format('Y-m-d') ?? '',
                'end_date'      => $t->end_date?->format('Y-m-d') ?? '',
                'status'        => $t->status,
                'original_data' => $t->original_data,
            ])
            ->values()
            ->all();
    }

    private function serializeNextWeekTasks(WeeklyReport $report): array
    {
        return $report->tasks
            ->where('section', 'next_week')
            ->map(fn ($t) => [
                'task_name'  => $t->task_name,
                'start_date' => $t->start_date?->format('Y-m-d') ?? '',
                'end_date'   => $t->end_date?->format('Y-m-d') ?? '',
            ])
            ->values()
            ->all();
    }

    // ─── 저장 ────────────────────────────────────────────────────────

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        $validated = $request->validate([
            'week_start_date' => 'required|date',
            'team_name'       => 'nullable|string|max:100',
            'author_name'     => 'required|string|max:100',
            'manager_name'    => 'nullable|string|max:100',
            'report_date'     => 'required|date',
            'summary'         => 'nullable|string',
            'special_notes'   => 'nullable|string',
            'current_tasks'   => 'nullable|string',
            'next_week_tasks' => 'nullable|string',
            'action'          => 'required|in:draft,submit,download',
        ]);

        $weekStart = $this->getWeekStart($validated['week_start_date']);
        $status    = $validated['action'] === 'submit' ? 'submitted' : 'draft';

        $report = WeeklyReport::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->first();

        $data = $this->buildReportData($project, $user, $weekStart, $validated, $status);

        if ($report) {
            $report->update($data);
        } else {
            $report = WeeklyReport::create($data);
        }

        $this->syncTasks($report, $validated['current_tasks'] ?? null, $validated['next_week_tasks'] ?? null);

        return $this->resolveRedirect($validated['action'], $project, $report, $request->input('popup') === '1');
    }

    public function update(Request $request, Project $project, WeeklyReport $weeklyReport): RedirectResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        if ($weeklyReport->project_id !== $project->id) abort(404);
        if ($weeklyReport->user_id !== $user->id && !$user->isAdmin()) abort(403);

        $validated = $request->validate([
            'week_start_date' => 'required|date',
            'team_name'       => 'nullable|string|max:100',
            'author_name'     => 'required|string|max:100',
            'manager_name'    => 'nullable|string|max:100',
            'report_date'     => 'required|date',
            'summary'         => 'nullable|string',
            'special_notes'   => 'nullable|string',
            'current_tasks'   => 'nullable|string',
            'next_week_tasks' => 'nullable|string',
            'action'          => 'required|in:draft,submit,download',
        ]);

        $weekStart = $this->getWeekStart($validated['week_start_date']);
        $status    = $validated['action'] === 'submit' ? 'submitted' : 'draft';

        // 주차 변경 시 동일 프로젝트+유저 중복 보고서 체크
        $conflict = WeeklyReport::where('project_id', $project->id)
            ->where('user_id', $weeklyReport->user_id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->where('id', '!=', $weeklyReport->id)
            ->exists();

        if ($conflict) {
            return back()->withErrors(['week_start_date' => '해당 주차에 이미 보고서가 존재합니다.'])->withInput();
        }

        $weeklyReport->update([
            'team_name'       => $validated['team_name'],
            'author_name'     => $validated['author_name'],
            'manager_name'    => $validated['manager_name'] ?? null,
            'report_date'     => $validated['report_date'],
            'week_start_date' => $weekStart->format('Y-m-d'),
            'year'            => $weekStart->year,
            'week_number'     => $weekStart->isoWeek(),
            'status'          => $status,
            'summary'         => $validated['summary'],
            'special_notes'   => $validated['special_notes'],
        ]);

        $this->syncTasks($weeklyReport, $validated['current_tasks'] ?? null, $validated['next_week_tasks'] ?? null);

        return $this->resolveRedirect($validated['action'], $project, $weeklyReport, $request->input('popup') === '1');
    }

    // ─── 워드 다운로드 ───────────────────────────────────────────────

    public function download(Project $project, WeeklyReport $weeklyReport): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorizeProject($project);
        if ($weeklyReport->project_id !== $project->id) abort(404);

        $weeklyReport->load(['project', 'user', 'tasks']);

        $writer = new DocxWriter();
        $writer->buildWeeklyReport($weeklyReport);

        $sanitize = static fn(string $s): string => str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|', ' '], '_', $s);
        $filename = $sanitize($project->name)
            . '_' . str_replace(' ', '', $weeklyReport->week_label)
            . '_' . $sanitize($weeklyReport->author_name)
            . '_' . $weeklyReport->report_date->format('Ymd')
            . '.docx';

        $dir = storage_path('app/temp');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $path = $dir . '/' . $filename;
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    // ─── JSON API ────────────────────────────────────────────────────

    public function previousTasks(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $weekStartDate = $request->get('week_start_date');
        if (!$weekStartDate) {
            return response()->json(['tasks' => [], 'found' => false]);
        }

        $prevWeekStart = Carbon::parse($weekStartDate)->subWeek()->startOfWeek(Carbon::MONDAY);

        $prevReport = WeeklyReport::where('project_id', $project->id)
            ->where('week_start_date', $prevWeekStart->format('Y-m-d'))
            ->orderByRaw("FIELD(status,'submitted','draft')")
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$prevReport) {
            return response()->json(['tasks' => [], 'found' => false]);
        }

        $tasks = $prevReport->tasks()
            ->where('section', 'next_week')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($t) => [
                'task_name'     => $t->task_name,
                'start_date'    => $t->start_date?->format('Y-m-d') ?? '',
                'end_date'      => $t->end_date?->format('Y-m-d') ?? '',
                'status'        => 'pending',
                'original_data' => [
                    'task_name'  => $t->task_name,
                    'start_date' => $t->start_date?->format('Y-m-d') ?? '',
                    'end_date'   => $t->end_date?->format('Y-m-d') ?? '',
                ],
            ]);

        return response()->json([
            'tasks'          => $tasks,
            'found'          => true,
            'from_week_date' => $prevWeekStart->format('Y.m.d'),
        ]);
    }

    public function checkConcurrent(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        $weekStartDate = $request->get('week_start_date');
        if (!$weekStartDate) {
            return response()->json(['concurrent' => false]);
        }

        $concurrent = WeeklyReport::where('project_id', $project->id)
            ->where('week_start_date', $weekStartDate)
            ->where('user_id', '!=', $user->id)
            ->where('status', 'draft')
            ->with('user:id,name')
            ->first();

        return response()->json([
            'concurrent' => (bool) $concurrent,
            'user_name'  => $concurrent?->user?->name,
        ]);
    }

    public function teamNames(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $names = WeeklyReport::where('project_id', $project->id)
            ->whereNotNull('team_name')
            ->where('team_name', '!=', '')
            ->distinct()
            ->pluck('team_name')
            ->values();

        return response()->json($names);
    }

    /**
     * 주간 보고서 요약 Quill 에디터의 이미지 업로드 (paste / 툴바).
     */
    public function uploadImage(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);
        $request->validate(['image' => 'required|image|max:5120']);  // 5MB
        $path = $request->file('image')->store('weekly-reports/images', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }

    // ─── 내부 헬퍼 ───────────────────────────────────────────────────

    private function buildReportData(Project $project, $user, Carbon $weekStart, array $validated, string $status): array
    {
        return [
            'project_id'      => $project->id,
            'user_id'         => $user->id,
            'company_group_id' => $user->company_group_id,
            'team_name'       => $validated['team_name'],
            'author_name'     => $validated['author_name'],
            'manager_name'    => $validated['manager_name'] ?? null,
            'report_date'     => $validated['report_date'],
            'year'            => $weekStart->year,
            'week_number'     => $weekStart->isoWeek(),
            'week_start_date' => $weekStart->format('Y-m-d'),
            'status'          => $status,
            'summary'         => $validated['summary'],
            'special_notes'   => $validated['special_notes'],
        ];
    }

    private function syncTasks(WeeklyReport $report, ?string $currentJson, ?string $nextJson): void
    {
        $report->tasks()->delete();

        $sort = 0;
        foreach (json_decode($currentJson ?? '[]', true) as $task) {
            if (empty(trim($task['task_name'] ?? ''))) continue;
            $status = in_array($task['status'] ?? '', ['completed', 'in_progress', 'pending'])
                ? $task['status'] : 'pending';
            WeeklyReportTask::create([
                'weekly_report_id' => $report->id,
                'section'          => 'current_week',
                'task_name'        => $task['task_name'],
                'start_date'       => $task['start_date'] ?: null,
                'end_date'         => $task['end_date'] ?: null,
                'status'           => $status,
                'original_data'    => $task['original_data'] ?? null,
                'sort_order'       => $sort++,
            ]);
        }

        $sort = 0;
        foreach (json_decode($nextJson ?? '[]', true) as $task) {
            if (empty(trim($task['task_name'] ?? ''))) continue;
            WeeklyReportTask::create([
                'weekly_report_id' => $report->id,
                'section'          => 'next_week',
                'task_name'        => $task['task_name'],
                'start_date'       => $task['start_date'] ?: null,
                'end_date'         => $task['end_date'] ?: null,
                'status'           => 'planned',
                'original_data'    => null,
                'sort_order'       => $sort++,
            ]);
        }
    }

    private function resolveRedirect(string $action, Project $project, WeeklyReport $report, bool $isPopup = false): RedirectResponse
    {
        if ($action === 'download') {
            return redirect()->route('projects.weekly-reports.download', [$project, $report]);
        }

        $msg = $action === 'submit' ? '주간 보고서가 제출되었습니다.' : '임시 저장되었습니다.';

        if ($isPopup) {
            $editUrl = route('projects.weekly-reports.edit', [$project, $report]) . '?popup=1';
            return redirect($editUrl)->with('success', $msg);
        }

        if ($action === 'submit') {
            return redirect()->route('projects.show', $project)->with('success', $msg);
        }
        return redirect()->route('projects.weekly-reports.edit', [$project, $report])->with('success', $msg);
    }

    private function getTeamNameList(Project $project): \Illuminate\Support\Collection
    {
        return WeeklyReport::where('project_id', $project->id)
            ->whereNotNull('team_name')
            ->where('team_name', '!=', '')
            ->distinct()
            ->pluck('team_name')
            ->filter()
            ->values();
    }

    // ─── 매니저: 선택 보고서 ZIP 다운로드 ───────────────────────────────

    public function bulkDownload(Request $request, Project $project): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        if (!$user->isAdmin() && $project->getMemberRole($user) !== 'manager') {
            abort(403);
        }

        $ids = array_filter(array_map('intval', $request->input('report_ids', [])));
        if (empty($ids)) {
            return back()->with('error', '다운로드할 보고서를 선택해주세요.');
        }

        $reports = WeeklyReport::where('project_id', $project->id)
            ->whereIn('id', $ids)
            ->with(['project', 'user', 'tasks'])
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', '선택된 보고서를 찾을 수 없습니다.');
        }

        $dir = storage_path('app/temp/bulk_' . uniqid());
        mkdir($dir, 0755, true);

        $zipPath = $dir . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($reports as $report) {
            $writer = new DocxWriter();
            $writer->buildWeeklyReport($report);

            $sanitize = static fn(string $s): string => str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|', ' '], '_', $s);
            $filename = $sanitize($project->name)
                . '_' . str_replace(' ', '', $report->week_label)
                . '_' . $sanitize($report->author_name)
                . '_' . $report->report_date->format('Ymd')
                . '.docx';

            $docxPath = $dir . '/' . $filename;
            $writer->save($docxPath);
            $zip->addFile($docxPath, $filename);
        }

        $zip->close();

        $zipName = sprintf('%s_주간보고_%s.zip',
            preg_replace('/[^가-힣a-zA-Z0-9_]/', '_', $project->name),
            now()->format('Ymd_His')
        );

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    // ─── 매니저: 웍스 분석 ─────────────────────────────────────────────

    public function analyze(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);
        $user = auth()->user();

        if (!$user->isAdmin() && $project->getMemberRole($user) !== 'manager') {
            abort(403);
        }

        $request->validate([
            'type'    => 'required|in:all,member',
            'user_id' => 'nullable|integer',
        ]);

        $query = WeeklyReport::where('project_id', $project->id)->with('tasks');

        if ($request->type === 'member' && $request->filled('user_id')) {
            $query->where('user_id', intval($request->user_id));
        }

        // 특정 주차 필터 (manager-summary 페이지에서 전달)
        if ($request->filled('week') && $request->week !== 'all') {
            $query->where('week_start_date', $request->week);
        }

        $reports = $query->orderBy('week_start_date')->get();

        if ($reports->isEmpty()) {
            return response()->json(['error' => '분석할 보고서가 없습니다.'], 422);
        }

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['error' => '웍스 분석을 사용하려면 웍스 API 키가 필요합니다. 관리자에게 문의해주세요.'], 422);
        }

        $reportLines = [];
        foreach ($reports as $r) {
            $completed  = $r->tasks->where('section', 'current_week')->where('status', 'completed')->pluck('task_name')->implode(', ');
            $inProgress = $r->tasks->where('section', 'current_week')->where('status', 'in_progress')->pluck('task_name')->implode(', ');
            $nextWeek   = $r->tasks->where('section', 'next_week')->pluck('task_name')->implode(', ');

            $reportLines[] = "=== [{$r->week_label}] {$r->author_name}" . ($r->team_name ? " ({$r->team_name})" : '') . " ===\n"
                . "요약: " . strip_tags($r->summary ?? '없음') . "\n"
                . "완료: " . ($completed ?: '없음') . "\n"
                . "진행: " . ($inProgress ?: '없음') . "\n"
                . "차주: " . ($nextWeek ?: '없음') . "\n"
                . ($r->special_notes ? "특이사항: {$r->special_notes}\n" : '');
        }

        $scope = $request->type === 'member'
            ? "담당자 '{$reports->first()->author_name}'의 " . $reports->count() . "개 주간 보고서"
            : "프로젝트 '{$project->name}'의 전체 " . $reports->count() . "개 주간 보고서";

        $isAll = $request->type === 'all';
        $systemPrompt = $isAll ? <<<PROMPT
당신은 프로젝트 관리 전문가입니다. 모든 팀원의 주간 보고서를 종합 분석하여 관리자에게 필요한 핵심 정보를 제공해주세요.

반드시 아래 세 가지 항목으로만 구성하세요:

## 진행 상황
팀 전체의 현재 진행 중인 업무와 완료 현황을 담당자별로 요약합니다.

## 이슈
지연되거나 막혀 있는 업무, 반복적으로 언급되는 문제, 특이사항을 구체적으로 나열합니다.

## 해결 방안
각 이슈에 대한 구체적이고 실행 가능한 해결 방안을 제안합니다.

마크다운 형식으로 작성하고, 각 항목은 불릿 포인트로 명확하게 정리하세요.
PROMPT : <<<PROMPT
당신은 프로젝트 관리 전문가입니다. 담당자의 주간 보고서 전체를 분석하여 관리자에게 필요한 핵심 정보를 제공해주세요.

반드시 아래 세 가지 항목으로만 구성하세요:

## 진행 상황
담당자의 주차별 업무 진행 흐름과 완료 현황을 시간 순서로 요약합니다.

## 이슈
지연, 반복 언급 문제, 미완료 업무, 특이사항을 구체적으로 나열합니다.

## 해결 방안
각 이슈에 대한 구체적이고 실행 가능한 해결 방안을 제안합니다.

마크다운 형식으로 작성하고, 각 항목은 불릿 포인트로 명확하게 정리하세요.
PROMPT;

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint()
            );
            ['text' => $text] = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => "다음은 {$scope}입니다:\n\n" . implode("\n", $reportLines)]],
                $systemPrompt
            );
            return response()->json(['result' => trim($text)]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            Log::warning('[WeeklyReport] 웍스 분석 실패: ' . $e->getMessage());
            $raw = $e->getMessage();
            if (str_contains($raw, 'credit balance') || str_contains($raw, 'insufficient') || str_contains($raw, 'quota') || str_contains($raw, 'billing')) {
                $msg = '웍스 서비스 크레딧 또는 한도가 초과되었습니다. 관리자에게 문의해주세요.';
            } elseif (str_contains($raw, 'NO_KEY') || str_contains($raw, '사용 가능한 웍스')) {
                $msg = '웍스 API 키가 설정되어 있지 않습니다. 관리자에게 문의해주세요.';
            } else {
                $msg = '웍스 분석 중 오류가 발생했습니다. 관리자에게 문의해주세요.';
            }
            return response()->json(['error' => $msg], 500);
        }
    }

}
