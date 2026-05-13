<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\CodeValidationService;
use App\Services\Agent\FrontendCodeAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CodeValidationController extends Controller
{
    private const CACHE_PREFIX    = 'ai-agent:code-validation:batch:';
    private const CACHE_TTL       = 3600;

    public function __construct(
        private readonly CodeValidationService $service,
        private readonly FrontendCodeAiService $codeService,
    ) {}

    // ── 목록 ──────────────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $staticAvailable = $this->service->loadValidation(0, 0) !== null
            ? true
            : app(\App\Services\Agent\CodeStaticAnalyzer::class)->isAvailable();

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->active()->orderBy('order')->get();

        $codeArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->get()->keyBy('scope_id');

        $validationArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->where('scope_type', 'screen')
            ->get()->keyBy('scope_id');

        $screenData = $screens->map(function ($screen) use ($codeArtifacts, $validationArtifacts, $project) {
            $codeArt   = $codeArtifacts->get($screen->id);
            $validArt  = $validationArtifacts->get($screen->id);
            $decoded   = $validArt ? (json_decode($validArt->content, true) ?? []) : null;

            return [
                'screen'           => $screen,
                'has_code'         => $codeArt !== null,
                'has_validation'   => $validArt !== null,
                'compliance_score' => $decoded['compliance_score'] ?? null,
                'violations_count' => !empty($decoded['violations']) ? count(array_filter($decoded['violations'] ?? [], fn($v) => empty($v['ignored']))) : 0,
                'critical_count'   => !empty($decoded['violations']) ? count(array_filter($decoded['violations'] ?? [], fn($v) => empty($v['ignored']) && ($v['severity'] ?? '') === 'critical')) : 0,
                'validated_at'     => $validArt?->meta['validated_at'] ?? null,
                'show_url'         => route('ai-agent.projects.dev.code-validation.show', [$project, $screen]),
                'validate_url'     => route('ai-agent.projects.dev.code-validation.screen.validate', [$project, $screen]),
            ];
        });

        $totalCount      = $screens->count();
        $codeCount       = $codeArtifacts->count();
        $validatedCount  = $validationArtifacts->count();
        $estimatedCost   = $this->service->estimatedCost($codeCount);

        // Aggregate scores
        $allScores = $validationArtifacts->map(function ($a) {
            $d = json_decode($a->content, true) ?? [];
            return $d['compliance_score'] ?? null;
        })->filter()->values();
        $avgScore = $allScores->count() > 0 ? (int) round($allScores->average()) : null;

        return view('ai-agent.dev.code-validation.index', [
            'project'          => $project,
            'screenData'       => $screenData,
            'staticAvailable'  => $staticAvailable,
            'totalCount'       => $totalCount,
            'codeCount'        => $codeCount,
            'validatedCount'   => $validatedCount,
            'estimatedCost'    => $estimatedCost,
            'avgScore'         => $avgScore,
            'batchStartUrl'    => route('ai-agent.projects.dev.code-validation.batch.start', $project),
            'batchSseUrlTpl'   => route('ai-agent.projects.dev.code-validation.batch.sse', [$project, 'SESSION_ID']),
            'exportUrl'        => route('ai-agent.projects.dev.code-validation.export', $project),
            'pageTitle'        => 'Output 검증 (T41)',
        ]);
    }

    // ── 배치 시작 ──────────────────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'screen_ids'     => 'nullable|array',
            'screen_ids.*'   => 'integer',
            'confirmed_cost' => 'boolean',
        ]);

        $confirmedCost = (bool) ($validated['confirmed_cost'] ?? false);

        if (!$confirmedCost) {
            $count     = isset($validated['screen_ids'])
                ? count($validated['screen_ids'])
                : AiAgentArtifact::where('project_id', $project->id)
                    ->where('type', ArtifactType::FRONTEND_CODE->value)
                    ->where('scope_type', 'screen')->count();
            $estimated = $this->service->estimatedCost($count);

            return response()->json([
                'requiresConfirmation' => true,
                'screenCount'          => $count,
                'estimatedCost'        => $estimated,
                'warning'              => $estimated > 5 ? 'COST_HIGH' : null,
            ]);
        }

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id' => $project->id,
            'user_id'    => (int) auth()->id(),
            'screen_ids' => $validated['screen_ids'] ?? null,
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ── SSE ────────────────────────────────────────────────────────────────────

    public function batchSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            Cache::forget(self::CACHE_PREFIX . $sessionId);
            $startedAt = microtime(true);

            try {
                $this->service->runBatch(
                    project:   $project,
                    userId:    $session['user_id'],
                    screenIds: $session['screen_ids'],
                    onEvent:   function (string $event, array $data) use ($startedAt) {
                        $this->sseEvent($event, array_merge($data, [
                            'elapsed' => round(microtime(true) - $startedAt, 1),
                        ]));
                    },
                );
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 화면 상세 ──────────────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $validationArtifact = $this->service->loadValidation($project->id, $screen->id);
        $codeArtifact       = $this->service->loadFrontendCode($project->id, $screen->id);
        $decoded            = $validationArtifact ? (json_decode($validationArtifact->content, true) ?? []) : null;

        $staticAvailable = app(\App\Services\Agent\CodeStaticAnalyzer::class)->isAvailable();

        return view('ai-agent.dev.code-validation.show', [
            'project'             => $project,
            'screen'              => $screen,
            'validationArtifact'  => $validationArtifact,
            'codeArtifact'        => $codeArtifact,
            'decoded'             => $decoded,
            'hasValidation'       => $validationArtifact !== null,
            'hasCode'             => $codeArtifact !== null,
            'staticAvailable'     => $staticAvailable,
            'validateUrl'         => route('ai-agent.projects.dev.code-validation.screen.validate', [$project, $screen]),
            'autoFixUrl'          => route('ai-agent.projects.dev.code-validation.screen.auto-fix', [$project, $screen]),
            'ignoreUrlTpl'        => route('ai-agent.projects.dev.code-validation.screen.ignore', [$project, $screen, 'VIOLATION_ID']),
            'destroyUrl'          => route('ai-agent.projects.dev.code-validation.screen.destroy', [$project, $screen]),
            'indexUrl'            => route('ai-agent.projects.dev.code-validation', $project),
            'historyUrl'          => $validationArtifact
                ? route('ai-agent.projects.artifact.versions', [$project, $validationArtifact])
                : null,
            'pageTitle'           => "[{$screen->screen_id}] {$screen->title} — Output 검증",
        ]);
    }

    // ── 단일 검증 ──────────────────────────────────────────────────────────────

    public function validateScreen(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        try {
            $result = $this->service->validateScreen($project, $screen, (int) auth()->id());

            return response()->json([
                'success'          => true,
                'compliance_score' => $result['compliance_score'],
                'violations_count' => $result['violations_count'],
                'artifact_id'      => $result['artifact']->id,
                'version'          => $result['artifact']->version,
                'tokens_in'        => $result['tokensIn'],
                'tokens_out'       => $result['tokensOut'],
                'model'            => $result['model'],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 자동 수정 ──────────────────────────────────────────────────────────────

    public function autoFix(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $validated = $request->validate([
            'violation_id' => 'required|string|max:50',
        ]);

        try {
            $result = $this->service->applyAutoFix(
                $project, $screen, $validated['violation_id'], (int) auth()->id()
            );
            return response()->json($result);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 무시 ───────────────────────────────────────────────────────────────────

    public function ignore(Request $request, Project $project, AiAgentScreen $screen, string $violationId): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        try {
            $this->service->ignoreViolation($project, $screen, $violationId, (int) auth()->id());
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── 삭제 ───────────────────────────────────────────────────────────────────

    public function destroy(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $artifact = $this->service->loadValidation($project->id, $screen->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->delete();
        return response()->json(['success' => true]);
    }

    // ── Export ─────────────────────────────────────────────────────────────────

    public function export(Project $project): Response
    {
        $this->authorizeProject($project);

        $md   = $this->service->exportMarkdown($project);
        $name = Str::slug($project->name) . '-code-validation-' . now()->format('Ymd') . '.md';

        return response($md, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── Private ────────────────────────────────────────────────────────────────

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
        if ($screen->project_id !== $project->id) abort(404);
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
