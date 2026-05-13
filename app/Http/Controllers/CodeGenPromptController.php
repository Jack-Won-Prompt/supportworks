<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\CodeGenPromptAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CodeGenPromptController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:code-prompt:batch:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly CodeGenPromptAiService $service,
    ) {}

    // ── 목록 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->active()->orderBy('order')->get();

        $existingArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_GEN_PROMPT->value)
            ->where('scope_type', 'screen')
            ->get()
            ->keyBy('scope_id');

        $screenData = $screens->map(function ($screen) use ($existingArtifacts) {
            $artifact = $existingArtifacts->get($screen->id);
            return [
                'screen'      => $screen,
                'artifact'    => $artifact,
                'has_prompt'  => $artifact !== null,
                'generated_at'=> $artifact?->meta['generated_at'] ?? null,
                'show_url'    => route('ai-agent.projects.pre-dev.code-prompts.show', [request()->route('project'), $screen]),
            ];
        });

        $totalCount   = $screens->count();
        $doneCount    = $existingArtifacts->count();
        $missingCount = $totalCount - $doneCount;

        return view('ai-agent.dev-prep.code-prompts.index', [
            'project'       => $project,
            'screenData'    => $screenData,
            'totalCount'    => $totalCount,
            'doneCount'     => $doneCount,
            'missingCount'  => $missingCount,
            'batchStartUrl' => route('ai-agent.projects.pre-dev.code-prompts.batch.start', $project),
            'batchSseUrlTpl'=> route('ai-agent.projects.pre-dev.code-prompts.batch.sse', [$project, 'SESSION_ID']),
            'cancelUrlTpl'  => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'     => '코드 생성 프롬프트',
        ]);
    }

    // ── 배치 시작 ────────────────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'screen_ids'    => 'nullable|array',
            'screen_ids.*'  => 'integer',
            'only_missing'  => 'boolean',
        ]);

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id'   => $project->id,
            'user_id'      => (int) auth()->id(),
            'screen_ids'   => $validated['screen_ids'] ?? null,
            'only_missing' => (bool) ($validated['only_missing'] ?? false),
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ── 배치 SSE 스트림 ──────────────────────────────────────────────────────

    public function batchSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '코드 생성 프롬프트 일괄 생성을 시작합니다...', 'progress' => 0]);
            $startedAt = microtime(true);
            Cache::forget(self::CACHE_PREFIX . $sessionId);

            try {
                $result = $this->service->generateBatch(
                    projectId:   $project->id,
                    screenIds:   $session['screen_ids'],
                    onlyMissing: $session['only_missing'],
                    userId:      $session['user_id'],
                    onProgress:  function (array $p) use ($startedAt) {
                        $this->sseEvent('progress', array_merge($p, [
                            'elapsed' => round(microtime(true) - $startedAt, 1),
                        ]));
                    },
                );

                $this->sseEvent('complete', [
                    'status'       => 'COMPLETED',
                    'total'        => $result['total'],
                    'done'         => $result['done'],
                    'failed_count' => $result['failed_count'],
                    'failed'       => $result['failed'],
                    'tokens_in'    => $result['tokens_in'],
                    'tokens_out'   => $result['tokens_out'],
                    'cost_usd'     => round($result['cost'], 4),
                    'model'        => $result['model'],
                    'elapsed'      => round(microtime(true) - $startedAt, 2),
                    'progress'     => 100,
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 화면별 상세 보기 ─────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $artifact = $this->getArtifact($project->id, $screen->id);

        return view('ai-agent.dev-prep.code-prompts.show', [
            'project'         => $project,
            'screen'          => $screen,
            'artifact'        => $artifact,
            'hasPrompt'       => $artifact !== null,
            'generateUrl'     => route('ai-agent.projects.pre-dev.code-prompts.screen.generate', [$project, $screen]),
            'updateUrl'       => route('ai-agent.projects.pre-dev.code-prompts.screen.update', [$project, $screen]),
            'destroyUrl'      => route('ai-agent.projects.pre-dev.code-prompts.screen.destroy', [$project, $screen]),
            'indexUrl'        => route('ai-agent.projects.pre-dev.code-prompts', $project),
            'historyUrl'      => $artifact
                ? route('ai-agent.projects.artifact.versions', [$project, $artifact])
                : null,
            'pageTitle'       => "[{$screen->screen_id}] {$screen->title} 코드 생성 프롬프트",
        ]);
    }

    // ── 단일 화면 생성 (POST) ────────────────────────────────────────────────

    public function generateForScreen(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        try {
            $result   = $this->service->generateForScreen(
                projectId: $project->id,
                screen:    $screen,
                userId:    (int) auth()->id(),
            );
            $artifact = $result['artifact'];

            return response()->json([
                'success'    => true,
                'artifact_id'=> $artifact->id,
                'version'    => $artifact->version,
                'content'    => $artifact->content,
                'tokens_in'  => $result['tokens_in'],
                'tokens_out' => $result['tokens_out'],
                'cost_usd'   => round($result['cost'], 4),
                'model'      => $result['model'],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 수동 편집 저장 ───────────────────────────────────────────────────────

    public function update(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $validated = $request->validate([
            'content' => 'required|string|min:10',
        ]);

        $artifact = $this->getArtifact($project->id, $screen->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '프롬프트 산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->updateWithVersion(
            content: $validated['content'],
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited', 'edited_at' => now()->toIso8601String()],
        );

        return response()->json(['success' => true, 'version' => $artifact->fresh()->version]);
    }

    // ── 삭제 ────────────────────────────────────────────────────────────────

    public function destroy(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $artifact = $this->getArtifact($project->id, $screen->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '프롬프트 산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->delete();

        return response()->json(['success' => true]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getArtifact(int $projectId, int $screenId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::CODE_GEN_PROMPT->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screenId)
            ->latest('id')->first();
    }

    private function authorizeProject(Project $project): void
    {
        $userId = (int) auth()->id();
        if (!ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists()
            && $project->created_by !== $userId) {
            abort(403);
        }
    }

    private function authorizeScreen(Project $project, AiAgentScreen $screen): void
    {
        if ($screen->project_id !== $project->id) {
            abort(404);
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
