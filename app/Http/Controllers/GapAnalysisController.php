<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentGap;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\GapAnalysisAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GapAnalysisController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:gap:sse:';
    private const CACHE_TTL    = 1800;

    public function __construct(
        private readonly GapAnalysisAiService $aiService,
    ) {}

    // ── 페이지 ──────────────────────────────────────────────────────────────

    public function projectIndex(Project $project): View
    {
        $this->authorizeProject($project);

        $stage    = $this->resolvePlanningStage($project);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::GAP_ANALYSIS,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     'Gap 분석',
            content:   '',
            userId:    (int) auth()->id(),
        );

        $content  = $artifact->content ? (json_decode($artifact->content, true) ?? null) : null;
        $gaps     = AiAgentGap::where('project_id', $project->id)
            ->where('artifact_id', $artifact->id)
            ->orderBy('gap_id')
            ->get();

        $prereqs  = $this->checkPrerequisites($project->id);

        return view('ai-agent.planning.gap.index', [
            'project'        => $project,
            'artifact'       => $artifact,
            'content'        => $content,
            'gaps'           => $gaps,
            'prereqs'        => $prereqs,
            'pageTitle'      => 'Gap 분석',
            'startUrl'       => route('ai-agent.projects.planning.gap.analyze.start', $project),
            'sseUrlTpl'      => route('ai-agent.projects.planning.gap.analyze.sse', [$project, 'SESSION_ID']),
            'cancelUrlTpl'   => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'saveUrl'        => route('ai-agent.projects.planning.gap.save', $project),
            'exportUrl'      => route('ai-agent.projects.planning.gap.export', $project),
            'gapStoreUrl'    => route('ai-agent.projects.planning.gap.items.store', $project),
            'historyUrl'     => route('ai-agent.projects.artifact.versions', [$project, $artifact]),
            'versionUrlTpl'  => route('ai-agent.projects.artifact.version', [$project, $artifact, 'VER']),
            'restoreUrlTpl'  => route('ai-agent.projects.artifact.restore', [$project, $artifact, 'VER']),
            'traceLinksUrl'  => route('ai-agent.projects.traceability.links', [$project, 'artifact', $artifact->id]),
            'traceImpactUrl' => route('ai-agent.projects.traceability.impact', [$project, 'artifact', $artifact->id]),
            'asIsUrl'        => route('ai-agent.projects.planning.as-is', $project),
            'toBeUrl'        => route('ai-agent.projects.planning.to-be', $project),
            'gapUpdateUrlTpl'  => route('ai-agent.projects.planning.gap.items.update',  [$project, 'GAPID']),
            'gapDestroyUrlTpl' => route('ai-agent.projects.planning.gap.items.destroy', [$project, 'GAPID']),
        ]);
    }

    // ── 사전 조건 확인 (JSON) ───────────────────────────────────────────────

    public function prerequisites(Project $project): JsonResponse
    {
        $this->authorizeProject($project);
        return response()->json($this->checkPrerequisites($project->id));
    }

    // ── SSE 분석 ────────────────────────────────────────────────────────────

    public function analyzeStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $prereqs = $this->checkPrerequisites($project->id);
        if (!$prereqs['as_is_ready'] || !$prereqs['to_be_ready']) {
            return response()->json([
                'success' => false,
                'message' => 'AS-IS 분석 또는 TO-BE 요구사항 분석이 완료되지 않았습니다.',
            ], 422);
        }

        $stage    = $this->resolvePlanningStage($project);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::GAP_ANALYSIS,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     'Gap 분석',
            content:   '',
            userId:    (int) auth()->id(),
        );

        $hasExistingGaps = AiAgentGap::where('project_id', $project->id)
            ->where('artifact_id', $artifact->id)
            ->exists();

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'artifact_id'  => $artifact->id,
            'project_id'   => $project->id,
            'user_id'      => (int) auth()->id(),
            'status'       => 'STARTING',
            'cancel'       => false,
            'has_existing' => $hasExistingGaps,
        ], self::CACHE_TTL);

        return response()->json([
            'success'     => true,
            'sessionId'   => $sessionId,
            'hasExisting' => $hasExistingGaps,
        ]);
    }

    public function analyzeSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STREAMING', 'progress' => 5]);
            $startedAt = microtime(true);

            try {
                $artifact = AiAgentArtifact::findOrFail($session['artifact_id']);

                $this->sseEvent('progress', [
                    'status'   => 'STREAMING',
                    'progress' => 15,
                    'message'  => 'AS-IS / TO-BE 데이터를 준비하는 중...',
                    'elapsed'  => round(microtime(true) - $startedAt, 1),
                ]);

                $stats = $this->aiService->analyzeWithStats($artifact, $session['user_id']);

                $this->sseEvent('complete', [
                    'status'    => 'COMPLETED',
                    'tokensIn'  => $stats['tokensIn'],
                    'tokensOut' => $stats['tokensOut'],
                    'elapsed'   => round(microtime(true) - $startedAt, 2),
                    'costUsd'   => $stats['costUsd'],
                    'result'    => $stats['result'],
                ]);
            } catch (\Throwable $e) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 저장 / 내보내기 ──────────────────────────────────────────────────────

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate(['executive_summary' => 'required|string']);

        $artifact = $this->getArtifact($project->id);
        $current  = $artifact->content ? (json_decode($artifact->content, true) ?? []) : [];

        $artifact->updateWithVersion(
            content: json_encode(array_merge($current, ['executive_summary' => $validated['executive_summary']]), JSON_UNESCAPED_UNICODE),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited'],
        );

        return response()->json(['success' => true]);
    }

    public function export(Project $project): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);

        $artifact = $this->getArtifact($project->id);
        $content  = json_decode($artifact->content ?? '{}', true) ?? [];
        $gaps     = AiAgentGap::where('project_id', $project->id)
            ->where('artifact_id', $artifact->id)
            ->orderBy('gap_id')
            ->get();

        $markdown = $this->buildMarkdown($project->name, $artifact, $content, $gaps);
        $filename = 'gap-' . Str::slug($project->name) . '-' . now()->format('Ymd') . '.md';

        return response($markdown, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Gap CRUD ─────────────────────────────────────────────────────────────

    public function gapStore(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'current_state'    => 'nullable|string',
            'target_state'     => 'nullable|string',
            'category'         => 'required|in:보안,기능,UX,성능,데이터,인프라,기타',
            'severity'         => 'required|in:high,medium,low',
            'estimated_effort' => 'nullable|in:high,medium,low',
        ]);

        $artifact = $this->getArtifact($project->id);
        $gapId    = AiAgentGap::nextGapId($project->id);

        $gap = AiAgentGap::create(array_merge($validated, [
            'gap_id'      => $gapId,
            'project_id'  => $project->id,
            'artifact_id' => $artifact->id,
            'source'      => 'manual',
            'created_by'  => (int) auth()->id(),
        ]));

        return response()->json(['success' => true, 'gap' => $gap]);
    }

    public function gapUpdate(Request $request, Project $project, AiAgentGap $gap): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($gap->project_id === $project->id, 403);

        $validated = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'current_state'    => 'sometimes|nullable|string',
            'target_state'     => 'sometimes|nullable|string',
            'category'         => 'sometimes|required|in:보안,기능,UX,성능,데이터,인프라,기타',
            'severity'         => 'sometimes|required|in:high,medium,low',
            'estimated_effort' => 'sometimes|nullable|in:high,medium,low',
            'recommended_actions' => 'sometimes|nullable|array',
        ]);

        $gap->update($validated);

        return response()->json(['success' => true, 'gap' => $gap->fresh()]);
    }

    public function gapDestroy(Project $project, AiAgentGap $gap): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($gap->project_id === $project->id, 403);

        $gap->delete();

        return response()->json(['success' => true]);
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function checkPrerequisites(int $projectId): array
    {
        $asIs = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::AS_IS_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $toBe = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::TO_BE_REQUIREMENTS->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $reqCount  = AiAgentRequirement::where('project_id', $projectId)->count();
        $asIsReady = $asIs !== null && !empty($asIs->content);
        $toBeReady = $toBe !== null && $reqCount > 0;

        $asIsContent  = $asIsReady ? (json_decode($asIs->content, true) ?? []) : [];
        $issueCount   = count($asIsContent['issues'] ?? []);

        return [
            'as_is_ready'        => $asIsReady,
            'to_be_ready'        => $toBeReady,
            'issue_count'        => $issueCount,
            'requirements_count' => $reqCount,
            'as_is_artifact_id'  => $asIs?->id,
            'to_be_artifact_id'  => $toBe?->id,
        ];
    }

    private function getArtifact(int $projectId): AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::GAP_ANALYSIS->value)
            ->where('scope_type', 'project')
            ->where('scope_id', $projectId)
            ->firstOrFail();
    }

    private function buildMarkdown(string $projectName, AiAgentArtifact $artifact, array $content, $gaps): string
    {
        $analyzedAt = $artifact->meta['analyzed_at'] ?? now()->toIso8601String();
        $summary    = $content['executive_summary'] ?? '(없음)';
        $risks      = $content['risks'] ?? [];
        $opps       = $content['improvement_opportunities'] ?? [];
        $recs       = $content['recommendations'] ?? [];

        $md  = "# Gap 분석 보고서\n\n";
        $md .= "**프로젝트:** {$projectName}  \n";
        $md .= "**분석 일시:** {$analyzedAt}  \n";
        $md .= "**버전:** v{$artifact->version}  \n\n---\n\n";

        $md .= "## 1. 종합 요약\n\n{$summary}\n\n";

        $md .= "## 2. Gap 목록 ({$gaps->count()}건)\n\n";
        foreach ($gaps as $gap) {
            $severity = strtoupper($gap->severity);
            $effort   = $gap->estimated_effort ? strtoupper($gap->estimated_effort) : '-';
            $md .= "### {$gap->gap_id}: {$gap->title}\n\n";
            $md .= "**카테고리:** {$gap->category} | **심각도:** {$severity} | **노력:** {$effort}  \n\n";
            if ($gap->current_state) { $md .= "**현재 상태:** {$gap->current_state}\n\n"; }
            if ($gap->target_state)  { $md .= "**목표 상태:** {$gap->target_state}\n\n"; }
            if (!empty($gap->related_requirement_ids)) {
                $md .= "**관련 요구사항:** " . implode(', ', $gap->related_requirement_ids) . "\n\n";
            }
            if (!empty($gap->recommended_actions)) {
                $md .= "**권장 조치:**\n";
                foreach ($gap->recommended_actions as $action) {
                    $md .= "- {$action}\n";
                }
                $md .= "\n";
            }
        }

        if ($opps) {
            $md .= "## 3. 개선 기회\n\n";
            foreach ($opps as $opp) {
                $md .= "### {$opp['title']}\n\n{$opp['description']}\n\n**기대 효과:** {$opp['expected_benefit']}\n\n";
            }
        }

        if ($risks) {
            $md .= "## 4. 리스크\n\n";
            foreach ($risks as $risk) {
                $likelihood = strtoupper($risk['likelihood'] ?? 'medium');
                $impact     = strtoupper($risk['impact'] ?? 'medium');
                $md .= "### {$risk['title']}\n\n";
                $md .= "**발생 가능성:** {$likelihood} | **영향도:** {$impact}  \n\n";
                $md .= "{$risk['description']}\n\n";
                if (!empty($risk['mitigation'])) {
                    $md .= "**완화 방안:** {$risk['mitigation']}\n\n";
                }
            }
        }

        if ($recs) {
            $md .= "## 5. 권장사항\n\n";
            if (!empty($recs['priority_actions'])) {
                $md .= "### 우선 실행 항목\n\n";
                foreach ($recs['priority_actions'] as $action) {
                    $md .= "- {$action}\n";
                }
                $md .= "\n";
            }
            if (!empty($recs['phasing_strategy'])) {
                $md .= "### 단계별 전략\n\n{$recs['phasing_strategy']}\n\n";
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
        if (ob_get_level() > 0) { ob_flush(); }
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
    }
}
