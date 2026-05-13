<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\IaDiagramAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IaDiagramController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:ia:sse:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly IaDiagramAiService $aiService,
    ) {}

    // ── 메인 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $planningDoc = $this->getPlanningDoc($project->id);
        $hasDocument = $planningDoc && strlen($planningDoc->content ?? '') > 100;

        $stage = $this->resolvePlanningStage($project);
        /** @var AiAgentArtifact $artifact */
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::IA_FLOW,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "{$project->name} IA / 화면 흐름도",
            content:   '',
            userId:    (int) auth()->id(),
        );

        $hasIa      = $artifact->content && strlen($artifact->content) > 50;
        $screenCount = AiAgentScreen::where('project_id', $project->id)->whereNull('archived_at')->count();
        $meta        = $artifact->meta ?? [];

        return view('ai-agent.planning.ia.index', [
            'project'      => $project,
            'artifact'     => $artifact,
            'hasDocument'  => $hasDocument,
            'hasIa'        => $hasIa,
            'screenCount'  => $screenCount,
            'meta'         => $meta,
            'pageTitle'    => 'IA / 화면 흐름도',
            // URLs
            'startUrl'         => route('ai-agent.projects.planning.ia.generate.start', $project),
            'sseUrlTpl'        => route('ai-agent.projects.planning.ia.generate.sse', [$project, 'SESSION_ID']),
            'saveUrl'          => route('ai-agent.projects.planning.ia.save', $project),
            'exportUrl'        => route('ai-agent.projects.planning.ia.export', $project),
            'regenerateUrl'    => route('ai-agent.projects.planning.ia.regenerate', $project),
            'cancelUrlTpl'     => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'historyUrl'       => route('ai-agent.projects.artifact.versions', [$project, $artifact]),
            'documentUrl'      => route('ai-agent.projects.planning.document', $project),
            'screensUrl'       => route('ai-agent.projects.planning.index', $project),
        ]);
    }

    // ── 생성 시작 ────────────────────────────────────────────────────────────

    public function generateStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $planningDoc = $this->getPlanningDoc($project->id);
        if (!$planningDoc || strlen($planningDoc->content ?? '') < 100) {
            return response()->json([
                'success' => false,
                'message' => '기획서가 아직 작성되지 않았습니다. 먼저 웍스 기획서를 생성해주세요.',
            ], 422);
        }

        $stage = $this->resolvePlanningStage($project);
        /** @var AiAgentArtifact $artifact */
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage->id,
            type:      ArtifactType::IA_FLOW,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "{$project->name} IA / 화면 흐름도",
            content:   '',
            userId:    (int) auth()->id(),
        );

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id'      => $project->id,
            'artifact_id'     => $artifact->id,
            'planning_doc_id' => $planningDoc->id,
            'user_id'         => (int) auth()->id(),
        ], self::CACHE_TTL);

        return response()->json([
            'success'      => true,
            'sessionId'    => $sessionId,
            'totalSections' => count(IaDiagramAiService::DIAGRAMS),
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

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => 'IA 다이어그램 생성을 시작합니다...', 'progress' => 2]);
            $startedAt = microtime(true);

            try {
                $artifact    = AiAgentArtifact::findOrFail($session['artifact_id']);
                $planningDoc = AiAgentArtifact::findOrFail($session['planning_doc_id']);
                $screens     = AiAgentScreen::where('project_id', $project->id)->whereNull('archived_at')->get();

                $this->sseEvent('status', ['status' => 'STREAMING', 'message' => '데이터 준비 완료', 'progress' => 5]);

                $stats = $this->aiService->generate(
                    artifact:    $artifact,
                    planningDoc: $planningDoc,
                    screens:     $screens,
                    userId:      $session['user_id'],
                    onProgress:  function (array $progress) use ($startedAt) {
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
            'content' => 'required|string|min:5',
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
        $filename = Str::slug($project->name) . '-IA-흐름도-' . now()->format('Ymd') . '.md';

        return response($markdown, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── 다이어그램 재생성 ────────────────────────────────────────────────────

    public function regenerateDiagram(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'diagram_key' => 'required|string|in:ia_diagram,flow_diagram',
        ]);

        $planningDoc = $this->getPlanningDoc($project->id);
        if (!$planningDoc || strlen($planningDoc->content ?? '') < 100) {
            return response()->json(['success' => false, 'message' => '기획서가 없습니다.'], 422);
        }

        $artifact = $this->getArtifact($project->id);
        $screens  = AiAgentScreen::where('project_id', $project->id)->whereNull('archived_at')->get();

        try {
            $result = $this->aiService->regenerateDiagram(
                artifact:    $artifact,
                diagramKey:  $validated['diagram_key'],
                planningDoc: $planningDoc,
                screens:     $screens,
                userId:      (int) auth()->id(),
            );

            return response()->json([
                'success'     => true,
                'diagram_key' => $validated['diagram_key'],
                'tokens_in'   => $result['tokens_in'],
                'tokens_out'  => $result['tokens_out'],
                'cost_usd'    => round($result['cost'], 4),
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 내부 헬퍼 ────────────────────────────────────────────────────────────

    private function getPlanningDoc(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    private function getArtifact(int $projectId): AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::IA_FLOW->value)
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
