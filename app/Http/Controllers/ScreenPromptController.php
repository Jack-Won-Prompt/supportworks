<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ScreenPromptAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScreenPromptController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:sp:sse:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly ScreenPromptAiService $aiService,
    ) {}

    // ── 목록 ──────────────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->orderBy('order')
            ->get();

        $artifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::SCREEN_PROMPTS->value)
            ->where('scope_type', 'screen')
            ->get()
            ->keyBy('scope_id');

        $config     = ProjectAiAgentConfig::forProject($project->id);
        $stackLabel = $config?->frontend_stack?->label() ?? '미설정';

        $missingIds = $screens
            ->filter(fn($s) => !$artifacts->has($s->id))
            ->pluck('id')
            ->values()
            ->toArray();

        return view('ai-agent.planning.screen-prompts.index', [
            'project'      => $project,
            'screens'      => $screens,
            'artifacts'    => $artifacts,
            'config'       => $config,
            'stackLabel'   => $stackLabel,
            'totalCount'   => $screens->count(),
            'promptCount'  => $artifacts->count(),
            'missingIds'   => $missingIds,
            'pageTitle'    => '화면 생성 프롬프트',
            // URLs
            'batchStartUrl' => route('ai-agent.projects.planning.prompts.batch.start', $project),
            'batchSseUrlTpl' => route('ai-agent.projects.planning.prompts.batch.sse', [$project, 'SESSION_ID']),
            'cancelUrlTpl'   => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'csrfToken'      => csrf_token(),
        ]);
    }

    // ── 단일 화면 상세 ────────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::SCREEN_PROMPTS->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->first();

        $keyword     = strtolower($screen->title ?? '');
        $requirements = AiAgentRequirement::where('project_id', $project->id)->get();
        $relatedReqs  = $keyword
            ? $requirements->filter(fn($r) => str_contains(strtolower($r->title . ' ' . $r->description), $keyword))
            : collect();

        $config    = ProjectAiAgentConfig::forProject($project->id);
        $prevScreen = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->where('order', '<', $screen->order)
            ->orderByDesc('order')
            ->first();
        $nextScreen = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->where('order', '>', $screen->order)
            ->orderBy('order')
            ->first();

        return view('ai-agent.planning.screen-prompts.show', [
            'project'       => $project,
            'screen'        => $screen,
            'artifact'      => $artifact,
            'relatedReqs'   => $relatedReqs->values(),
            'config'        => $config,
            'prevScreen'    => $prevScreen,
            'nextScreen'    => $nextScreen,
            'pageTitle'     => "[{$screen->screen_id}] {$screen->title} 프롬프트",
            // URLs
            'generateUrl'    => route('ai-agent.projects.planning.prompts.generate', [$project, $screen]),
            'updateUrl'      => route('ai-agent.projects.planning.prompts.update', [$project, $screen]),
            'destroyUrl'     => route('ai-agent.projects.planning.prompts.destroy', [$project, $screen]),
            'historyUrl'     => $artifact ? route('ai-agent.projects.artifact.versions', [$project, $artifact]) : null,
            'indexUrl'       => route('ai-agent.projects.planning.prompts', $project),
            'mockupUrl'      => route('ai-agent.projects.planning.mockups', $project),
            'csrfToken'      => csrf_token(),
        ]);
    }

    // ── 단일 생성 ─────────────────────────────────────────────────────────────

    public function generateOne(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $stage = $this->resolvePlanningStage($project);

        try {
            $result = $this->aiService->generateForScreen(
                projectId: $project->id,
                stageId:   $stage->id,
                screen:    $screen,
                userId:    (int) auth()->id(),
            );

            return response()->json([
                'success'    => true,
                'content'    => $result['artifact']->content,
                'version'    => $result['artifact']->version,
                'tokens_in'  => $result['tokens_in'],
                'tokens_out' => $result['tokens_out'],
                'cost_usd'   => round($result['cost'], 4),
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 사용자 편집 저장 ──────────────────────────────────────────────────────

    public function update(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $validated = $request->validate(['content' => 'required|string|min:10']);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::SCREEN_PROMPTS->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->firstOrFail();

        $artifact->updateWithVersion(
            content: $validated['content'],
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited', 'edited_at' => now()->toIso8601String()],
        );

        $screen->update(['generation_prompt' => $validated['content']]);

        return response()->json(['success' => true, 'version' => $artifact->fresh()->version]);
    }

    // ── 프롬프트 삭제 ─────────────────────────────────────────────────────────

    public function destroy(Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::SCREEN_PROMPTS->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->delete();

        $screen->update(['generation_prompt' => null]);

        return response()->json(['success' => true]);
    }

    // ── 일괄 생성 시작 ────────────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'only_missing' => 'boolean',
            'screen_ids'   => 'nullable|array',
            'screen_ids.*' => 'integer',
        ]);

        $onlyMissing = $validated['only_missing'] ?? true;

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->orderBy('order')
            ->get();

        if ($onlyMissing) {
            $existing = AiAgentArtifact::where('project_id', $project->id)
                ->where('type', ArtifactType::SCREEN_PROMPTS->value)
                ->where('scope_type', 'screen')
                ->pluck('scope_id')
                ->toArray();
            $screens = $screens->filter(fn($s) => !in_array($s->id, $existing));
        }

        if (!empty($validated['screen_ids'])) {
            $screens = $screens->filter(fn($s) => in_array($s->id, $validated['screen_ids']));
        }

        if ($screens->isEmpty()) {
            return response()->json(['success' => false, 'message' => '처리할 화면이 없습니다.'], 422);
        }

        $stage     = $this->resolvePlanningStage($project);
        $sessionId = Str::uuid()->toString();

        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id' => $project->id,
            'stage_id'   => $stage->id,
            'screen_ids' => $screens->pluck('id')->toArray(),
            'user_id'    => (int) auth()->id(),
        ], self::CACHE_TTL);

        return response()->json([
            'success'       => true,
            'sessionId'     => $sessionId,
            'totalScreens'  => $screens->count(),
        ]);
    }

    // ── 일괄 생성 SSE ─────────────────────────────────────────────────────────

    public function batchSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '프롬프트 일괄 생성을 시작합니다...', 'progress' => 2]);
            $startedAt = microtime(true);

            try {
                $stats = $this->aiService->generateBatch(
                    projectId:  $project->id,
                    stageId:    $session['stage_id'],
                    screenIds:  $session['screen_ids'],
                    userId:     $session['user_id'],
                    onProgress: function (array $progress) use ($startedAt) {
                        $this->sseEvent('screen_progress', array_merge($progress, [
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

    // ── 내부 헬퍼 ─────────────────────────────────────────────────────────────

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
        $userId   = (int) auth()->id();
        $isMember = ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists();
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
