<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactFile;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\AsIsAnalysisAiService;
use App\Services\Agent\AsIsAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsIsAnalysisController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:as-is:sse:';
    private const CACHE_TTL    = 1800;

    public function __construct(
        private readonly AsIsAnalysisService   $service,
        private readonly AsIsAnalysisAiService $aiService,
    ) {}

    // ── 프로젝트 스코프 ─────────────────────────────────────────────────────────

    public function projectIndex(Project $project): View
    {
        $this->authorizeProject($project);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            scopeType: 'project',
            scopeId:   $project->id,
            userId:    (int) auth()->id(),
        );

        $files  = $artifact->files()->get();
        $status = $this->service->getAnalysisStatus($artifact);
        $result = $artifact->content ? (json_decode($artifact->content, true) ?? null) : null;

        return view('ai-agent.planning.as-is.index', [
            'project'        => $project,
            'artifact'       => $artifact,
            'files'          => $files,
            'status'         => $status,
            'result'         => $result,
            'pageTitle'      => 'AS-IS 분석 (프로젝트 전체)',
            'scopeLabel'     => '프로젝트 전체',
            'startUrl'       => route('ai-agent.projects.planning.as-is.analyze.start', $project),
            'sseUrlTpl'      => route('ai-agent.projects.planning.as-is.analyze.sse', [$project, 'SESSION_ID']),
            'cancelUrlTpl'   => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'saveUrl'        => route('ai-agent.projects.planning.as-is.save', $project),
            'exportUrl'      => route('ai-agent.projects.planning.as-is.export', $project),
            'statusUrl'      => route('ai-agent.projects.planning.as-is.status', $project),
            'historyUrl'     => route('ai-agent.projects.artifact.versions', [$project, $artifact]),
            'versionUrlTpl'  => route('ai-agent.projects.artifact.version', [$project, $artifact, 'VER']),
            'restoreUrlTpl'  => route('ai-agent.projects.artifact.restore', [$project, $artifact, 'VER']),
            'traceLinksUrl'  => route('ai-agent.projects.traceability.links', [$project, 'artifact', $artifact->id]),
            'traceImpactUrl' => route('ai-agent.projects.traceability.impact', [$project, 'artifact', $artifact->id]),
        ]);
    }

    public function projectUpload(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'files'   => 'required|array|min:1|max:10',
            'files.*' => 'file|max:51200',
        ]);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            scopeType: 'project',
            scopeId:   $project->id,
            userId:    (int) auth()->id(),
        );

        $this->service->attachFiles($artifact, $request->file('files'), (int) auth()->id());

        return redirect()->route('ai-agent.projects.planning.as-is', $project)
            ->with('success', '파일이 업로드되었습니다.');
    }

    public function projectDeleteFile(Project $project, AiAgentArtifactFile $file): RedirectResponse
    {
        $this->authorizeProject($project);
        $this->authorizeFile($project, $file);

        $this->service->removeFile($file);

        return redirect()->route('ai-agent.projects.planning.as-is', $project)
            ->with('success', '파일이 삭제되었습니다.');
    }

    public function projectStatus(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'as_is_analysis')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->first();

        if (!$artifact) {
            return response()->json(['counts' => [], 'ready' => true]);
        }

        return response()->json($this->service->getAnalysisStatus($artifact));
    }

    public function projectAnalyzeStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            scopeType: 'project',
            scopeId:   $project->id,
            userId:    (int) auth()->id(),
        );

        return $this->analyzeStart($artifact);
    }

    public function projectAnalyzeSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        return $this->analyzeSseStream($sessionId, $project->id);
    }

    public function projectSave(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate(['result' => 'required|array']);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'as_is_analysis')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->firstOrFail();

        $artifact->updateWithVersion(
            content: json_encode($validated['result'], JSON_UNESCAPED_UNICODE),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited'],
        );

        return response()->json(['success' => true]);
    }

    public function projectExport(Project $project): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'as_is_analysis')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->firstOrFail();

        $result   = json_decode($artifact->content ?? '{}', true) ?? [];
        $markdown = $this->buildMarkdown($project->name . ' — 프로젝트 전체', $artifact, $result);
        $filename = 'as-is-' . Str::slug($project->name) . '-' . now()->format('Ymd') . '.md';

        return response($markdown, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── 화면 스코프 ─────────────────────────────────────────────────────────────

    public function screenIndex(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            scopeType: 'screen',
            scopeId:   $screen->id,
            userId:    (int) auth()->id(),
        );

        $files  = $artifact->files()->get();
        $status = $this->service->getAnalysisStatus($artifact);
        $result = $artifact->content ? (json_decode($artifact->content, true) ?? null) : null;

        return view('ai-agent.planning.as-is.screen', [
            'project'        => $project,
            'screen'         => $screen,
            'artifact'       => $artifact,
            'files'          => $files,
            'status'         => $status,
            'result'         => $result,
            'pageTitle'      => "AS-IS 분석 [{$screen->screen_id}]",
            'scopeLabel'     => "{$screen->screen_id} — {$screen->title}",
            'startUrl'       => route('ai-agent.projects.planning.screens.as-is.analyze.start', [$project, $screen]),
            'sseUrlTpl'      => route('ai-agent.projects.planning.screens.as-is.analyze.sse', [$project, $screen, 'SESSION_ID']),
            'cancelUrlTpl'   => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'saveUrl'        => route('ai-agent.projects.planning.screens.as-is.save', [$project, $screen]),
            'exportUrl'      => route('ai-agent.projects.planning.screens.as-is.export', [$project, $screen]),
            'statusUrl'      => route('ai-agent.projects.planning.screens.as-is.status', [$project, $screen]),
            'historyUrl'     => route('ai-agent.projects.artifact.versions', [$project, $artifact]),
            'versionUrlTpl'  => route('ai-agent.projects.artifact.version', [$project, $artifact, 'VER']),
            'restoreUrlTpl'  => route('ai-agent.projects.artifact.restore', [$project, $artifact, 'VER']),
            'traceLinksUrl'  => route('ai-agent.projects.traceability.links', [$project, 'artifact', $artifact->id]),
            'traceImpactUrl' => route('ai-agent.projects.traceability.impact', [$project, 'artifact', $artifact->id]),
        ]);
    }

    public function screenUpload(Request $request, Project $project, AiAgentScreen $screen): RedirectResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $request->validate([
            'files'   => 'required|array|min:1|max:10',
            'files.*' => 'file|max:51200',
        ]);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            scopeType: 'screen',
            scopeId:   $screen->id,
            userId:    (int) auth()->id(),
        );

        $this->service->attachFiles($artifact, $request->file('files'), (int) auth()->id());

        return redirect()->route('ai-agent.projects.planning.screens.as-is', [$project, $screen])
            ->with('success', '파일이 업로드되었습니다.');
    }

    public function screenDeleteFile(Project $project, AiAgentScreen $screen, AiAgentArtifactFile $file): RedirectResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = $file->artifact;
        abort_unless(
            $artifact && $artifact->project_id === $project->id
                && $artifact->scope_type === 'screen'
                && $artifact->scope_id === $screen->id,
            403
        );

        $this->service->removeFile($file);

        return redirect()->route('ai-agent.projects.planning.screens.as-is', [$project, $screen])
            ->with('success', '파일이 삭제되었습니다.');
    }

    public function screenStatus(Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'as_is_analysis')
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->first();

        if (!$artifact) {
            return response()->json(['counts' => [], 'ready' => true]);
        }

        return response()->json($this->service->getAnalysisStatus($artifact));
    }

    public function screenAnalyzeStart(Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            scopeType: 'screen',
            scopeId:   $screen->id,
            userId:    (int) auth()->id(),
        );

        return $this->analyzeStart($artifact);
    }

    public function screenAnalyzeSse(Project $project, AiAgentScreen $screen, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);
        return $this->analyzeSseStream($sessionId, $project->id);
    }

    public function screenSave(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $validated = $request->validate(['result' => 'required|array']);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'as_is_analysis')
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->firstOrFail();

        $artifact->updateWithVersion(
            content: json_encode($validated['result'], JSON_UNESCAPED_UNICODE),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited'],
        );

        return response()->json(['success' => true]);
    }

    public function screenExport(Project $project, AiAgentScreen $screen): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'as_is_analysis')
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->firstOrFail();

        $result   = json_decode($artifact->content ?? '{}', true) ?? [];
        $label    = "{$screen->screen_id} — {$screen->title}";
        $markdown = $this->buildMarkdown($label, $artifact, $result);
        $filename = 'as-is-' . Str::slug($screen->screen_id) . '-' . now()->format('Ymd') . '.md';

        return response($markdown, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function analyzeStart(AiAgentArtifact $artifact): JsonResponse
    {
        $artifact->load('files');

        $unparsed = $artifact->files->filter(
            fn($f) => !in_array($f->parse_status, ['completed', 'failed'], true)
        );

        if ($unparsed->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '파일 파싱이 완료되지 않았습니다. 잠시 후 다시 시도해주세요.',
            ], 422);
        }

        if ($artifact->files->where('parse_status', 'completed')->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '분석 가능한 파일이 없습니다. 파일을 먼저 업로드하고 파싱이 완료될 때까지 기다려주세요.',
            ], 422);
        }

        $lastAnalyzedAt = $artifact->fresh()->meta['analyzed_at'] ?? null;
        if ($lastAnalyzedAt && now()->diffInMinutes($lastAnalyzedAt) < 5) {
            return response()->json([
                'success' => false,
                'message' => '5분 이내 재실행은 불가합니다. 잠시 후 다시 시도해주세요.',
            ], 429);
        }

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'artifact_id' => $artifact->id,
            'project_id'  => $artifact->project_id,
            'user_id'     => (int) auth()->id(),
            'status'      => 'STARTING',
            'cancel'      => false,
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    private function analyzeSseStream(string $sessionId, int $projectId): StreamedResponse
    {
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $projectId) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $projectId) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STREAMING', 'progress' => 5]);

            $startedAt = microtime(true);

            try {
                $artifact = AiAgentArtifact::findOrFail($session['artifact_id']);

                $this->sseEvent('progress', [
                    'status'   => 'STREAMING',
                    'progress' => 20,
                    'message'  => '파일 내용을 준비하는 중...',
                    'elapsed'  => round(microtime(true) - $startedAt, 1),
                ]);

                $stats = $this->aiService->analyzeWithStats($artifact, $session['user_id']);

                $elapsed = round(microtime(true) - $startedAt, 2);

                $this->sseEvent('complete', [
                    'status'    => 'COMPLETED',
                    'tokensIn'  => $stats['tokensIn'],
                    'tokensOut' => $stats['tokensOut'],
                    'elapsed'   => $elapsed,
                    'costUsd'   => $stats['costUsd'],
                    'result'    => $stats['result'],
                ]);
            } catch (\Throwable $e) {
                $this->sseEvent('error', [
                    'status'  => 'ERROR',
                    'message' => $e->getMessage(),
                ]);
            }
        }, 200, $this->sseHeaders());
    }

    private function buildMarkdown(string $scopeLabel, AiAgentArtifact $artifact, array $result): string
    {
        $analyzedAt = $artifact->meta['analyzed_at'] ?? now()->toIso8601String();
        $summary    = $result['summary'] ?? '(없음)';
        $issues     = $result['issues'] ?? [];
        $categories = $result['categories'] ?? [];
        $mapping    = $result['source_mapping'] ?? [];

        $md  = "# AS-IS 분석 보고서\n\n";
        $md .= "**분석 대상:** {$scopeLabel}  \n";
        $md .= "**분석 일시:** {$analyzedAt}  \n";
        $md .= "**버전:** v{$artifact->version}  \n\n";
        $md .= "---\n\n";

        $md .= "## 1. 현황 요약\n\n{$summary}\n\n";

        $md .= "## 2. 이슈 목록 (" . count($issues) . "건)\n\n";
        foreach ($issues as $i => $issue) {
            $num      = $i + 1;
            $severity = strtoupper($issue['severity'] ?? 'low');
            $md .= "### {$num}. [{$severity}] [{$issue['category']}] {$issue['title']}\n\n";
            $md .= "{$issue['description']}\n\n";
            if (!empty($issue['source_files'])) {
                $md .= '**출처:** ' . implode(', ', $issue['source_files']) . "\n\n";
            }
        }

        if ($categories) {
            $md .= "## 3. 카테고리별 분석\n\n";
            foreach ($categories as $cat => $content) {
                $md .= "### {$cat}\n\n{$content}\n\n";
            }
        }

        if ($mapping) {
            $md .= "## 4. 파일별 주요 발견사항\n\n";
            foreach ($mapping as $file => $findings) {
                $md .= "### {$file}\n\n";
                foreach ($findings as $finding) {
                    $md .= "- {$finding}\n";
                }
                $md .= "\n";
            }
        }

        return $md;
    }

    private function resolvePlanningStage(Project $project): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', 'planning')
            ->firstOrFail();
    }

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

    private function authorizeFile(Project $project, AiAgentArtifactFile $file): void
    {
        $artifact = $file->artifact;
        abort_unless(
            $artifact && $artifact->project_id === $project->id,
            403
        );
    }

    private function sseHeaders(): array
    {
        return [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ];
    }

    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
