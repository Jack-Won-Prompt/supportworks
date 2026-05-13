<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Schedule;
use App\Services\Agent\GanttSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiPlanningScreenController extends Controller
{
    public function __construct(private GanttSyncService $ganttSync) {}

    // ── 목록 ─────────────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $activeScreens   = AiAgentScreen::where('project_id', $project->id)
            ->active()
            ->with('assignee')
            ->orderBy('screen_id')
            ->get();

        $archivedScreens = AiAgentScreen::where('project_id', $project->id)
            ->archived()
            ->with('assignee')
            ->orderBy('screen_id')
            ->get();

        $lastSyncedAt = AiAgentScreen::where('project_id', $project->id)
            ->fromGantt()
            ->max('updated_at');

        return view('ai-agent.planning.screens.index', [
            'project'         => $project,
            'activeScreens'   => $activeScreens,
            'archivedScreens' => $archivedScreens,
            'lastSyncedAt'    => $lastSyncedAt,
            'pageTitle'       => '작업 항목 (화면 목록)',
            'stageLabel'      => '단계 1: 기획',
        ]);
    }

    // ── 수동 추가 ─────────────────────────────────────────────────────────────

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'assigned_to'    => 'nullable|exists:users,id',
            'scheduled_start'=> 'nullable|date',
            'scheduled_end'  => 'nullable|date|after_or_equal:scheduled_start',
        ]);

        $screenId = AiAgentScreen::nextScreenId($project->id);

        AiAgentScreen::create([
            'project_id'          => $project->id,
            'screen_id'           => $screenId,
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'source'              => 'manual',
            'status'              => 'draft',
            'assigned_to_user_id' => $validated['assigned_to'] ?? null,
            'scheduled_start'     => $validated['scheduled_start'] ?? null,
            'scheduled_end'       => $validated['scheduled_end'] ?? null,
        ]);

        return redirect()->route('ai-agent.projects.planning.index', $project)
            ->with('success', "{$screenId} 화면이 추가되었습니다.");
    }

    // ── 상세 ─────────────────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $screen->load(['assignee', 'ganttTask', 'traceabilityLinks']);

        return view('ai-agent.planning.screens.show', [
            'project'    => $project,
            'screen'     => $screen,
            'pageTitle'  => $screen->screen_id . ' — ' . $screen->title,
            'stageLabel' => '단계 1: 기획',
        ]);
    }

    // ── 편집 ─────────────────────────────────────────────────────────────────

    public function update(Request $request, Project $project, AiAgentScreen $screen): RedirectResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'assigned_to'    => 'nullable|exists:users,id',
            'scheduled_start'=> 'nullable|date',
            'scheduled_end'  => 'nullable|date|after_or_equal:scheduled_start',
        ]);

        $screen->update([
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to'] ?? null,
            'scheduled_start'     => $validated['scheduled_start'] ?? null,
            'scheduled_end'       => $validated['scheduled_end'] ?? null,
        ]);

        return redirect()->route('ai-agent.projects.planning.screens.show', [$project, $screen])
            ->with('success', '화면 정보가 수정되었습니다.');
    }

    // ── 아카이브 / 복원 ───────────────────────────────────────────────────────

    public function archive(Project $project, AiAgentScreen $screen): RedirectResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $screen->archive();

        return redirect()->route('ai-agent.projects.planning.index', $project)
            ->with('success', "{$screen->screen_id} 화면이 아카이브 처리되었습니다.");
    }

    public function restore(Project $project, AiAgentScreen $screen): RedirectResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $screen->restore();

        return redirect()->route('ai-agent.projects.planning.index', $project)
            ->with('success', "{$screen->screen_id} 화면이 복원되었습니다.");
    }

    // ── 간트 동기화 미리보기 ──────────────────────────────────────────────────

    public function ganttPreview(Project $project): View
    {
        $this->authorizeProject($project);

        $preview = $this->ganttSync->preview($project);

        return view('ai-agent.planning.screens.sync-preview', [
            'project'     => $project,
            'preview'     => $preview,
            'pageTitle'   => '간트 동기화',
            'stageLabel'  => '단계 1: 기획',
        ]);
    }

    // ── 간트 동기화 실행 ──────────────────────────────────────────────────────

    public function syncFromGantt(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'schedule_ids'   => 'required|array|min:1',
            'schedule_ids.*' => 'integer|exists:legacy_schedules,id',
            'archive_orphans'=> 'nullable|boolean',
        ]);

        $result = $this->ganttSync->sync(
            $project,
            $request->input('schedule_ids'),
            (int) auth()->id()
        );

        $archived = 0;
        if ($request->boolean('archive_orphans')) {
            $archived = $this->ganttSync->archiveOrphaned($project);
        }

        $result['archived'] = $archived;

        $msg = "동기화 완료 — 신규 {$result['created']}건, 업데이트 {$result['updated']}건";
        if ($archived > 0) {
            $msg .= ", 아카이브 {$archived}건";
        }

        return redirect()->route('ai-agent.projects.planning.index', $project)
            ->with('success', $msg);
    }

    // ── 권한 ──────────────────────────────────────────────────────────────────

    private function authorizeProject(Project $project): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        abort_unless(
            ProjectMember::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
