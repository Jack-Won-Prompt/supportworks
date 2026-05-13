<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\CodeReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CodeReviewController extends Controller
{
    private const CACHE_PREFIX    = 'ai-agent:code-review:batch:';
    private const CACHE_TTL       = 3600;
    private const COST_PER_SCREEN = 0.40;

    public function __construct(
        private readonly CodeReviewService $service,
    ) {}

    // ── 메인 페이지 ───────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->active()->orderBy('order')->get();

        $screenArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'screen')
            ->get()->keyBy('scope_id');

        $feCount = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')->count();
        $beCount = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')->count();
        $t41Count = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->where('scope_type', 'screen')->count();

        $t44Artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::API_INTEGRATION->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
        $t44Meta = $t44Artifact ? (json_decode($t44Artifact->content, true)['analysis']['$metadata'] ?? null) : null;

        $systemArtifact = $this->service->loadSystemReview($project->id);
        $systemData     = $systemArtifact ? (json_decode($systemArtifact->content, true) ?? []) : null;

        $screenData = $screens->map(function ($screen) use ($screenArtifacts, $project) {
            $artifact  = $screenArtifacts->get($screen->id);
            $decoded   = $artifact ? (json_decode($artifact->content, true) ?? []) : null;
            $additional = $decoded ? array_filter($decoded['additional_findings'] ?? [], fn($f) => empty($f['ignored']) && empty($f['fixed'])) : [];
            return [
                'screen'           => $screen,
                'artifact'         => $artifact,
                'has_review'       => $artifact !== null,
                'compliance_score' => $decoded['compliance_score'] ?? null,
                'findings_count'   => count($additional),
                'critical_count'   => count(array_filter($additional, fn($f) => ($f['severity'] ?? '') === 'critical')),
                'reviewed_at'      => $artifact?->meta['reviewed_at'] ?? null,
                'show_url'         => route('ai-agent.projects.dev.code-review.screen.show', [$project, $screen]),
            ];
        });

        $totalScreens = $screens->count();
        $doneScreens  = $screenArtifacts->count();
        $avgScore     = $doneScreens > 0
            ? (int) round($screenArtifacts->avg(fn($a) => $a->meta['compliance_score'] ?? 0))
            : 0;

        // Category averages from screen reviews
        $categoryAvgs = $this->computeCategoryAverages($screenArtifacts);

        return view('ai-agent.dev.code-review.index', [
            'project'         => $project,
            'screenData'      => $screenData,
            'systemData'      => $systemData,
            'totalScreens'    => $totalScreens,
            'doneScreens'     => $doneScreens,
            'avgScore'        => $avgScore,
            'categoryAvgs'    => $categoryAvgs,
            'feCount'         => $feCount,
            'beCount'         => $beCount,
            't41Count'        => $t41Count,
            't44Meta'         => $t44Meta,
            'estimatedCost'   => $this->service->estimatedCost($totalScreens),
            'batchStartUrl'   => route('ai-agent.projects.dev.code-review.start', $project),
            'batchSseUrlTpl'  => route('ai-agent.projects.dev.code-review.sse', [$project, 'SESSION_ID']),
            'exportUrl'       => route('ai-agent.projects.dev.code-review.export', $project),
            'systemUrl'       => route('ai-agent.projects.dev.code-review.system', $project),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'       => '웍스 코드 리뷰 (T45)',
        ]);
    }

    // ── 배치 시작 ─────────────────────────────────────────────────────────────

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
            $count = isset($validated['screen_ids'])
                ? count($validated['screen_ids'])
                : AiAgentScreen::where('project_id', $project->id)->active()->count();

            return response()->json([
                'requiresConfirmation' => true,
                'screenCount'          => $count,
                'estimatedCost'        => $this->service->estimatedCost($count),
                'warning'              => $this->service->estimatedCost($count) > 5 ? 'COST_HIGH' : null,
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

    // ── SSE 스트림 ────────────────────────────────────────────────────────────

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

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '코드 리뷰를 시작합니다...', 'progress' => 0]);
            Cache::forget(self::CACHE_PREFIX . $sessionId);

            try {
                $this->service->runBatch(
                    project:   $project,
                    userId:    $session['user_id'],
                    onEvent:   fn(string $ev, array $data) => $this->sseEvent($ev, $data),
                    screenIds: $session['screen_ids'],
                );
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 화면별 상세 ──────────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $artifact = $this->service->loadScreenReview($project->id, $screen->id);
        $decoded  = $artifact ? (json_decode($artifact->content, true) ?? []) : null;

        return view('ai-agent.dev.code-review.show', [
            'project'         => $project,
            'screen'          => $screen,
            'artifact'        => $artifact,
            'decoded'         => $decoded,
            'hasReview'       => $artifact !== null,
            'regenerateUrl'   => route('ai-agent.projects.dev.code-review.screen.regenerate', [$project, $screen]),
            'autoFixUrlTpl'   => route('ai-agent.projects.dev.code-review.screen.auto-fix', [$project, $screen]),
            'ignoreUrlTpl'    => route('ai-agent.projects.dev.code-review.screen.ignore', [$project, $screen, 'FINDING_ID']),
            'indexUrl'        => route('ai-agent.projects.dev.code-review', $project),
            'pageTitle'       => "[{$screen->screen_id}] {$screen->title} — 웍스 코드 리뷰",
        ]);
    }

    // ── 단일 화면 재리뷰 ─────────────────────────────────────────────────────

    public function regenerate(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        try {
            $result = $this->service->reviewScreen($project, $screen, (int) auth()->id());

            return response()->json([
                'success'          => true,
                'artifact_id'      => $result['artifact']->id,
                'compliance_score' => $result['compliance_score'],
                'findings_count'   => $result['findings_count'],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 자동 수정 ─────────────────────────────────────────────────────────────

    public function autoFix(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $validated = $request->validate(['finding_id' => 'required|string']);

        try {
            $result = $this->service->applyAutoFix(
                project:   $project,
                screen:    $screen,
                findingId: $validated['finding_id'],
                userId:    (int) auth()->id(),
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 무시 ─────────────────────────────────────────────────────────────────

    public function ignore(Request $request, Project $project, AiAgentScreen $screen, string $findingId): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        try {
            $this->service->ignoreFinding($project, $screen, $findingId, (int) auth()->id());
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 시스템 종합 리뷰 페이지 ──────────────────────────────────────────────

    public function system(Project $project): View
    {
        $this->authorizeProject($project);

        $artifact = $this->service->loadSystemReview($project->id);
        $decoded  = $artifact ? (json_decode($artifact->content, true) ?? []) : null;

        return view('ai-agent.dev.code-review.system', [
            'project'   => $project,
            'artifact'  => $artifact,
            'decoded'   => $decoded,
            'indexUrl'  => route('ai-agent.projects.dev.code-review', $project),
            'pageTitle' => '시스템 종합 코드 리뷰 (T45)',
        ]);
    }

    // ── 내보내기 ─────────────────────────────────────────────────────────────

    public function export(Project $project): Response
    {
        $this->authorizeProject($project);

        $md   = $this->service->exportMarkdown($project);
        $slug = Str::slug($project->name);
        $name = "{$slug}-code-review-" . now()->format('Ymd') . '.md';

        return response($md, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function computeCategoryAverages($artifacts): array
    {
        $cats = ['spec_compliance', 'code_quality', 'security', 'best_practices', 'performance', 'data_flow', 'integration'];
        $totals = array_fill_keys($cats, 0);
        $count  = 0;

        foreach ($artifacts as $a) {
            $data = json_decode($a->content, true) ?? [];
            $cs   = $data['category_scores'] ?? [];
            if (!empty($cs)) {
                foreach ($cats as $cat) {
                    $totals[$cat] += (int) ($cs[$cat] ?? 0);
                }
                $count++;
            }
        }

        if ($count === 0) return array_fill_keys($cats, 0);

        return array_map(fn($v) => (int) round($v / $count), $totals);
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
