<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\PlanningDocumentAiService;
use App\Services\Agent\PlanningDocumentDataContext;
use App\Services\Agent\PlanningTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanningDocumentController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:doc:sse:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly PlanningDocumentAiService   $aiService,
        private readonly PlanningDocumentDataContext  $dataContext,
        private readonly PlanningTemplateService     $templateService,
    ) {}

    // ── 메인 페이지 (4가지 상태) ────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $stage = $this->resolvePlanningStage($project);
        /** @var AiAgentArtifact $artifact */
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::PLANNING_DOC,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "{$project->name} 기획서",
            content:   '',
            userId:    (int) auth()->id(),
        );

        $dataStatus      = $this->dataContext->getDataStatus($project->id);
        $missingRequired = $this->dataContext->getMissingData($project->id);
        $canProceed      = empty($missingRequired);

        $template        = $this->templateService->getActive();
        $sectionStatuses = $template
            ? $this->templateService->getSectionStatuses($template, $dataStatus)
            : [];

        $hasDocument   = $artifact->content && strlen($artifact->content) > 100;
        $screenCount   = AiAgentScreen::where('project_id', $project->id)->whereNull('archived_at')->count();
        $aiSectionCount = ($template ? $template->getAiSectionCount() : 7) + $screenCount;

        $meta           = $artifact->meta ?? [];
        $failedSections = $meta['failed_sections'] ?? [];

        return view('ai-agent.planning.document.index', [
            'project'         => $project,
            'artifact'        => $artifact,
            'dataStatus'      => $dataStatus,
            'missingRequired' => $missingRequired,
            'canProceed'      => $canProceed,
            'hasDocument'     => $hasDocument,
            'sectionStatuses' => $sectionStatuses,
            'template'        => $template,
            'screenCount'     => $screenCount,
            'aiSectionCount'  => $aiSectionCount,
            'failedSections'  => $failedSections,
            'meta'            => $meta,
            'pageTitle'       => '웍스 기획서',
            // URLs
            'startUrl'         => route('ai-agent.projects.planning.document.generate.start', $project),
            'sseUrlTpl'        => route('ai-agent.projects.planning.document.generate.sse', [$project, 'SESSION_ID']),
            'saveUrl'          => route('ai-agent.projects.planning.document.save', $project),
            'exportUrl'        => route('ai-agent.projects.planning.document.export', $project),
            'regenerateUrl'    => route('ai-agent.projects.planning.document.regenerate', $project),
            'cancelUrlTpl'     => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'historyUrl'       => route('ai-agent.projects.artifact.versions', [$project, $artifact]),
            'traceLinksUrl'    => route('ai-agent.projects.traceability.links',  [$project, 'artifact', $artifact->id]),
            'asIsUrl'          => route('ai-agent.projects.planning.as-is',   $project),
            'toBeUrl'          => route('ai-agent.projects.planning.to-be',   $project),
            'gapUrl'           => route('ai-agent.projects.planning.gap',     $project),
            'screensUrl'       => route('ai-agent.projects.planning.index', $project),
            'templatePreviewUrl' => route('ai-agent.projects.planning.document.template', $project),
        ]);
    }

    // ── 템플릿 구조 미리보기 (T21에서 이동) ─────────────────────────────────

    public function templatePreview(Project $project): View
    {
        $this->authorizeProject($project);

        $template        = $this->templateService->getActive();
        $dataStatus      = $this->dataContext->getDataStatus($project->id);
        $missingRequired = $this->dataContext->getMissingData($project->id);
        $canProceed      = empty($missingRequired);
        $sectionStatuses = $template
            ? $this->templateService->getSectionStatuses($template, $dataStatus)
            : [];

        return view('ai-agent.planning.document.preview-template', [
            'project'         => $project,
            'template'        => $template,
            'dataStatus'      => $dataStatus,
            'missingRequired' => $missingRequired,
            'canProceed'      => $canProceed,
            'sectionStatuses' => $sectionStatuses,
            'pageTitle'       => '기획서 템플릿 미리보기',
            'asIsUrl'         => route('ai-agent.projects.planning.as-is',   $project),
            'toBeUrl'         => route('ai-agent.projects.planning.to-be',   $project),
            'gapUrl'          => route('ai-agent.projects.planning.gap',     $project),
            'screensUrl'      => route('ai-agent.projects.planning.index', $project),
        ]);
    }

    // ── 데이터 상태 (JSON) ────────────────────────────────────────────────────

    public function dataStatus(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        return response()->json([
            'data_status'      => $this->dataContext->getDataStatus($project->id),
            'missing_required' => $this->dataContext->getMissingData($project->id),
            'can_proceed'      => empty($this->dataContext->getMissingData($project->id)),
        ]);
    }

    // ── 생성 시작 ────────────────────────────────────────────────────────────

    public function generateStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $missing = $this->dataContext->getMissingData($project->id);
        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => '필수 데이터가 준비되지 않았습니다: ' . implode(', ', $missing),
            ], 422);
        }

        $stage = $this->resolvePlanningStage($project);
        /** @var AiAgentArtifact $artifact */
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::PLANNING_DOC,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "{$project->name} 기획서",
            content:   '',
            userId:    (int) auth()->id(),
        );

        $screenCount    = AiAgentScreen::where('project_id', $project->id)->whereNull('archived_at')->count();
        $template       = $this->templateService->getActive();
        $aiSectionCount = ($template ? $template->getAiSectionCount() : 7) + $screenCount;

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id'  => $project->id,
            'artifact_id' => $artifact->id,
            'user_id'     => (int) auth()->id(),
            'status'      => 'pending',
        ], self::CACHE_TTL);

        return response()->json([
            'success'       => true,
            'sessionId'     => $sessionId,
            'hasExisting'   => $artifact->content && strlen($artifact->content) > 100,
            'totalSections' => $aiSectionCount,
        ]);
    }

    // ── SSE 생성 스트림 ──────────────────────────────────────────────────────

    public function generateSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '기획서 작성을 시작합니다...', 'progress' => 2]);
            $startedAt = microtime(true);

            try {
                $artifact = AiAgentArtifact::findOrFail($session['artifact_id']);
                $context  = $this->dataContext->build($project->id);

                $this->sseEvent('status', ['status' => 'STREAMING', 'message' => '데이터 컨텍스트 준비 완료', 'progress' => 5]);

                $stats = $this->aiService->generateAllAndSave(
                    artifact:   $artifact,
                    context:    $context,
                    userId:     $session['user_id'],
                    onProgress: function (array $progress) use ($startedAt) {
                        $this->sseEvent('section_progress', array_merge($progress, [
                            'elapsed' => round(microtime(true) - $startedAt, 1),
                        ]));
                    },
                );

                $this->sseEvent('complete', [
                    'status'       => 'COMPLETED',
                    'total'        => $stats['total'],
                    'failed_count' => $stats['failed_count'],
                    'failed'       => $stats['failed'],
                    'tokens_in'    => $stats['tokens_in'],
                    'tokens_out'   => $stats['tokens_out'],
                    'cost_usd'     => round($stats['cost'], 4),
                    'model'        => $stats['model'],
                    'elapsed'      => round(microtime(true) - $startedAt, 2),
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 저장 (사용자 편집) ────────────────────────────────────────────────────

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'content' => 'required|string|min:10',
        ]);

        $artifact = $this->getArtifact($project->id);

        $artifact->updateWithVersion(
            content: $validated['content'],
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited', 'edited_at' => now()->toIso8601String()],
        );

        return response()->json(['success' => true, 'version' => $artifact->fresh()->version]);
    }

    // ── 내보내기 ─────────────────────────────────────────────────────────────

    public function export(Request $request, Project $project): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);

        $artifact = $this->getArtifact($project->id);
        $markdown = $artifact->content ?? '';
        $filename = Str::slug($project->name) . '-기획서-' . now()->format('Ymd') . '.md';

        return response($markdown, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── 섹션 재생성 ──────────────────────────────────────────────────────────

    public function regenerateSection(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'section_key' => 'required|string|max:100',
        ]);

        $missing = $this->dataContext->getMissingData($project->id);
        if (!empty($missing)) {
            return response()->json(['success' => false, 'message' => '필수 데이터가 없습니다.'], 422);
        }

        $artifact = $this->getArtifact($project->id);

        try {
            $result = $this->aiService->regenerateSection(
                artifact:   $artifact,
                sectionKey: $validated['section_key'],
                userId:     (int) auth()->id(),
            );

            return response()->json([
                'success'    => true,
                'section_key' => $validated['section_key'],
                'tokens_in'  => $result['tokens_in'],
                'tokens_out' => $result['tokens_out'],
                'cost_usd'   => round($result['cost'], 4),
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 내부 헬퍼 ────────────────────────────────────────────────────────────

    private function getArtifact(int $projectId): AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->firstOrFail();
    }

    private function resolvePlanningStage(Project $project): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', 'planning')
            ->first()
            ?? AiAgentProjectStage::create([
                'project_id' => $project->id,
                'type' => 'planning',
                'status'     => 'in_progress',
            ]);
    }

    private function authorizeProject(Project $project): void
    {
        $userId = (int) auth()->id();

        $isMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$isMember && $project->created_by !== $userId) {
            abort(403);
        }
    }

    // ── SSE 헬퍼 (다른 컨트롤러와 동일 패턴) ────────────────────────────────

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
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        ob_implicit_flush(true);
    }
}
