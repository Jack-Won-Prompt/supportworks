<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\DesignReviewService;
use App\Services\Agent\ReviewContextLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignReviewController extends Controller
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly DesignReviewService $reviewService,
        private readonly ReviewContextLoader $contextLoader,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $hasPat  = $user->aiAgentCredential?->hasPat() ?? false;
        $context = $this->contextLoader->load($project);
        $artifact = $this->reviewService->getCurrent($project);

        $report   = null;
        if ($artifact) {
            $raw    = is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true);
            $report = $raw;
        }

        $pageTitle  = '디자인 일관성 검수';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.review.index', compact(
            'project', 'hasPat', 'context', 'artifact', 'report', 'pageTitle', 'stageLabel'
        ));
    }

    public function start(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $context = $this->contextLoader->load($project);

        if ($context['mapped_screens'] === 0) {
            return response()->json(['success' => false, 'message' => '매핑된 화면이 없습니다. T31 화면 매핑을 먼저 완료하세요.'], 422);
        }

        if (!$hasPat = $user->aiAgentCredential?->hasPat()) {
            return response()->json(['success' => false, 'message' => 'Figma PAT이 설정되지 않았습니다.'], 403);
        }

        $sessionId = Str::uuid()->toString();
        $this->reviewService->createSession($project, $user, $sessionId);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    public function sse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);

        $session = $this->reviewService->getSession($sessionId);

        return response()->stream(function () use ($project, $sessionId, $session) {
            @set_time_limit(0);
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            /** @var \App\Models\User $user */
            $user = \App\Models\User::find($session['user_id']);
            if (!$user) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '사용자를 찾을 수 없습니다.']);
                return;
            }

            try {
                $this->reviewService->runReview(
                    project:   $project,
                    user:      $user,
                    sessionId: $sessionId,
                    onEvent:   fn(string $event, array $data) => $this->sseEvent($event, $data),
                );
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '검수 중 오류: ' . $e->getMessage()]);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function screenShow(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $result   = $this->reviewService->getScreenResult($project, $screen);
        $artifact = $this->reviewService->getCurrent($project);

        $pageTitle  = "{$screen->screen_id} — 검수 상세";
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.review.screens.show', compact(
            'project', 'screen', 'result', 'artifact', 'pageTitle', 'stageLabel'
        ));
    }

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'screen_id'   => ['required', 'string'],
            'ignored_ids' => ['nullable', 'array'],
        ]);

        $artifact = $this->reviewService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '검수 결과가 없습니다.'], 404);
        }

        $this->reviewService->updateIgnoredViolations(
            artifact:   $artifact,
            screenId:   $request->screen_id,
            ignoredIds: $request->ignored_ids ?? [],
            userId:     (int) Auth::id(),
        );

        return response()->json(['success' => true, 'message' => '저장되었습니다.']);
    }

    public function export(Project $project): \Illuminate\Http\Response|JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->reviewService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['message' => '검수 결과가 없습니다.'], 404);
        }

        $slug = Str::slug($project->name);
        $date = now()->format('Ymd');

        return response($artifact->content, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"design-review-{$slug}-{$date}.json\"",
        ]);
    }

    public function regenerate(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        if (!$screen->hasFigmaMapping()) {
            return response()->json(['success' => false, 'message' => '화면에 Figma 프레임이 연결되지 않았습니다.'], 422);
        }

        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $context = $this->contextLoader->load($project);

        try {
            $imageUrl = null;
            if ($screen->figma_file_key) {
                $client    = app(\App\Services\Agent\Figma\FigmaClientFactory::class)->forUser($user);
                $fetched   = $client->getImages($screen->figma_file_key, [$screen->figma_frame_id], 'png', 0.75);
                $imageUrl  = $fetched[$screen->figma_frame_id] ?? null;
            }

            $reviewResult = app(\App\Services\Agent\AiDesignReviewer::class)->reviewScreen(
                screen:        $screen,
                context:       $context,
                userId:        $user->id,
                projectId:     $project->id,
                figmaImageUrl: $imageUrl,
            );

            // Merge into existing artifact
            $artifact = $this->reviewService->getCurrent($project);
            if ($artifact) {
                $data = is_array($artifact->content)
                    ? $artifact->content
                    : json_decode($artifact->content, true);

                $data['violations_by_screen'][$screen->screen_id] = array_merge(
                    $reviewResult['result'],
                    ['screen_name' => $screen->title, 'figma_url' => $screen->figma_url]
                );

                $artifact->updateWithVersion(
                    content: json_encode($data, JSON_UNESCAPED_UNICODE),
                    userId:  $user->id,
                    meta:    ['change_type' => 'single_regenerate', 'screen_id' => $screen->screen_id],
                );
            }

            return response()->json([
                'success' => true,
                'score'   => $reviewResult['result']['compliance_score'],
                'message' => "{$screen->screen_id} 재검수 완료 ({$reviewResult['result']['compliance_score']}점)",
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '재검수 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function clearOutputBuffer(): void
    {
        while (ob_get_level()) ob_end_clean();
        ob_implicit_flush(true);
    }

    private function sseEvent(string $event, array $data): void
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        echo "event: {$event}\ndata: {$payload}\n\n";
        if (function_exists('fastcgi_finish_request')) {
            // not used in SSE — just flush
        }
        flush();
    }

    private function authorizeProject(Project $project): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isAdmin()) return;
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
