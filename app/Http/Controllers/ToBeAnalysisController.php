<?php

namespace App\Http\Controllers;

use App\Enums\Agent\RequirementPriority;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactFile;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\ToBeAnalysisAiService;
use App\Services\Agent\ToBeAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToBeAnalysisController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:to-be:sse:';
    private const CACHE_TTL    = 1800;

    public function __construct(
        private readonly ToBeAnalysisService   $service,
        private readonly ToBeAnalysisAiService $aiService,
    ) {}

    // ── 페이지 ──────────────────────────────────────────────────────────────

    public function projectIndex(Project $project): View
    {
        $this->authorizeProject($project);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            userId:    (int) auth()->id(),
        );

        $files        = $artifact->files()->get();
        $status       = $this->service->getAnalysisStatus($artifact);
        $content      = $artifact->content ? (json_decode($artifact->content, true) ?? null) : null;
        $requirements = AiAgentRequirement::where('project_id', $project->id)
            ->where('artifact_id', $artifact->id)
            ->orderBy('req_id')
            ->get();

        return view('ai-agent.planning.to-be.index', [
            'project'        => $project,
            'artifact'       => $artifact,
            'files'          => $files,
            'status'         => $status,
            'content'        => $content,
            'requirements'   => $requirements,
            'pageTitle'      => 'TO-BE 요구사항 분석',
            'startUrl'       => route('ai-agent.projects.planning.to-be.analyze.start', $project),
            'sseUrlTpl'      => route('ai-agent.projects.planning.to-be.analyze.sse', [$project, 'SESSION_ID']),
            'cancelUrlTpl'   => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'saveUrl'        => route('ai-agent.projects.planning.to-be.save', $project),
            'exportUrl'      => route('ai-agent.projects.planning.to-be.export', $project),
            'statusUrl'      => route('ai-agent.projects.planning.to-be.status', $project),
            'reqStoreUrl'    => route('ai-agent.projects.planning.to-be.req.store', $project),
            'historyUrl'     => route('ai-agent.projects.artifact.versions', [$project, $artifact]),
            'versionUrlTpl'  => route('ai-agent.projects.artifact.version', [$project, $artifact, 'VER']),
            'restoreUrlTpl'  => route('ai-agent.projects.artifact.restore', [$project, $artifact, 'VER']),
            'traceLinksUrl'  => route('ai-agent.projects.traceability.links', [$project, 'artifact', $artifact->id]),
            'traceImpactUrl' => route('ai-agent.projects.traceability.impact', [$project, 'artifact', $artifact->id]),
        ]);
    }

    // ── 파일 관리 ───────────────────────────────────────────────────────────

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
            userId:    (int) auth()->id(),
        );

        $this->service->attachFiles($artifact, $request->file('files'), (int) auth()->id());

        return redirect()->route('ai-agent.projects.planning.to-be', $project)
            ->with('success', '파일이 업로드되었습니다.');
    }

    public function projectDeleteFile(Project $project, AiAgentArtifactFile $file): RedirectResponse
    {
        $this->authorizeProject($project);
        $this->authorizeFile($project, $file);

        $this->service->removeFile($file);

        return redirect()->route('ai-agent.projects.planning.to-be', $project)
            ->with('success', '파일이 삭제되었습니다.');
    }

    public function projectStatus(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'to_be_requirements')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->first();

        if (!$artifact) {
            return response()->json(['counts' => [], 'ready' => true]);
        }

        return response()->json($this->service->getAnalysisStatus($artifact));
    }

    // ── SSE 분석 ────────────────────────────────────────────────────────────

    public function projectAnalyzeStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = $this->service->createOrGetAnalysis(
            projectId: $project->id,
            stageId:   $stage->id,
            userId:    (int) auth()->id(),
        );

        return $this->analyzeStart($artifact);
    }

    public function projectAnalyzeSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        return $this->analyzeSseStream($sessionId, $project->id);
    }

    // ── 저장 / 내보내기 ──────────────────────────────────────────────────────

    public function projectSave(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate(['overview' => 'required|string']);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'to_be_requirements')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->firstOrFail();

        $current = $artifact->content ? (json_decode($artifact->content, true) ?? []) : [];

        $artifact->updateWithVersion(
            content: json_encode(array_merge($current, ['overview' => $validated['overview']]), JSON_UNESCAPED_UNICODE),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited'],
        );

        return response()->json(['success' => true]);
    }

    public function projectExport(Project $project): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'to_be_requirements')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->firstOrFail();

        $content      = json_decode($artifact->content ?? '{}', true) ?? [];
        $requirements = AiAgentRequirement::where('project_id', $project->id)
            ->where('artifact_id', $artifact->id)
            ->orderBy('req_id')
            ->get();

        $markdown = $this->buildMarkdown($project->name, $artifact, $content, $requirements);
        $filename = 'to-be-' . Str::slug($project->name) . '-' . now()->format('Ymd') . '.md';

        return response($markdown, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── 요구사항 CRUD ────────────────────────────────────────────────────────

    public function requirementUpdate(Request $request, Project $project, AiAgentRequirement $requirement): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($requirement->project_id === $project->id, 403);

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'rationale'   => 'sometimes|nullable|string',
            'priority'    => 'sometimes|required|in:must,should,could,wont',
            'category'    => 'sometimes|nullable|string|max:100',
            'status'      => 'sometimes|required|in:draft,confirmed,deferred,removed',
        ]);

        $requirement->update($validated);

        return response()->json(['success' => true, 'requirement' => $requirement->fresh()]);
    }

    public function requirementStore(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'rationale'   => 'nullable|string',
            'priority'    => 'required|in:must,should,could,wont',
            'category'    => 'nullable|string|max:100',
        ]);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', 'to_be_requirements')
            ->where('scope_type', 'project')
            ->where('scope_id', $project->id)
            ->firstOrFail();

        $reqId = AiAgentRequirement::nextReqId($project->id);

        $requirement = AiAgentRequirement::create(array_merge($validated, [
            'project_id'  => $project->id,
            'artifact_id' => $artifact->id,
            'req_id'      => $reqId,
            'source'      => 'to_be',
            'status'      => 'draft',
        ]));

        return response()->json(['success' => true, 'requirement' => $requirement]);
    }

    public function requirementDestroy(Project $project, AiAgentRequirement $requirement): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($requirement->project_id === $project->id, 403);

        $requirement->delete();

        return response()->json(['success' => true]);
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

        $hasExistingReqs = AiAgentRequirement::where('project_id', $artifact->project_id)
            ->where('artifact_id', $artifact->id)
            ->exists();

        // 재분석 경고는 프론트에서 확인 후 force=true로 재호출
        // (실제 삭제/재생성은 ToBeAnalysisAiService::persistResult 에서 수행)

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'artifact_id'  => $artifact->id,
            'project_id'   => $artifact->project_id,
            'user_id'      => (int) auth()->id(),
            'status'       => 'STARTING',
            'cancel'       => false,
            'has_existing' => $hasExistingReqs,
        ], self::CACHE_TTL);

        return response()->json([
            'success'     => true,
            'sessionId'   => $sessionId,
            'hasExisting' => $hasExistingReqs,
        ]);
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

    private function buildMarkdown(string $projectName, AiAgentArtifact $artifact, array $content, $requirements): string
    {
        $analyzedAt      = $artifact->meta['analyzed_at'] ?? now()->toIso8601String();
        $overview        = $content['overview'] ?? '(없음)';
        $prioritySummary = $content['priority_summary'] ?? [];

        $md  = "# TO-BE 요구사항 분석 보고서\n\n";
        $md .= "**프로젝트:** {$projectName}  \n";
        $md .= "**분석 일시:** {$analyzedAt}  \n";
        $md .= "**버전:** v{$artifact->version}  \n\n";
        $md .= "---\n\n";

        $md .= "## 1. 개요\n\n{$overview}\n\n";

        if ($prioritySummary) {
            $md .= "## 2. 우선순위 요약\n\n";
            $md .= "| 우선순위 | 건수 |\n|---|---|\n";
            foreach (['must' => 'MUST', 'should' => 'SHOULD', 'could' => 'COULD', 'wont' => 'WONT'] as $key => $label) {
                $count = $prioritySummary[$key] ?? 0;
                $md .= "| {$label} | {$count} |\n";
            }
            $md .= "\n";
        }

        $md .= "## 3. 요구사항 목록 ({$requirements->count()}건)\n\n";

        foreach ($requirements as $req) {
            $priority = strtoupper($req->priority->value ?? $req->priority ?? 'should');
            $md .= "### {$req->req_id}: {$req->title}\n\n";
            $md .= "**우선순위:** {$priority}  \n";
            $md .= "**카테고리:** {$req->category}  \n";
            $md .= "**상태:** {$req->status}  \n\n";
            if ($req->description) {
                $md .= "{$req->description}\n\n";
            }
            if ($req->rationale) {
                $md .= "**도출 근거:** {$req->rationale}\n\n";
            }
            if (!empty($req->source_files)) {
                $files = is_array($req->source_files) ? implode(', ', $req->source_files) : $req->source_files;
                $md .= "**출처:** {$files}\n\n";
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
