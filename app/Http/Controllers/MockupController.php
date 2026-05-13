<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\FrontendStack;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\MockupAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MockupController extends Controller
{
    private const CACHE_PREFIX      = 'ai-agent:mk:sse:';
    private const CACHE_TTL         = 3600;
    private const ESTIMATED_COST_PER_SCREEN = 0.25; // USD per screen (estimate)
    private const COOLDOWN_MINUTES  = 5;

    public function __construct(
        private readonly MockupAiService $aiService,
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
            ->where('type', ArtifactType::MOCKUP->value)
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

        return view('ai-agent.planning.mockups.index', [
            'project'      => $project,
            'screens'      => $screens,
            'artifacts'    => $artifacts,
            'config'       => $config,
            'stackLabel'   => $stackLabel,
            'totalCount'   => $screens->count(),
            'mockupCount'  => $artifacts->count(),
            'missingIds'   => $missingIds,
            'pageTitle'    => '웍스 샘플 화면 (목업)',
            'batchStartUrl'  => route('ai-agent.projects.planning.mockups.batch.start', $project),
            'batchSseUrlTpl' => route('ai-agent.projects.planning.mockups.batch.sse', [$project, 'SESSION_ID']),
            'csrfToken'      => csrf_token(),
        ]);
    }

    // ── 단일 화면 상세 ────────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->first();

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

        $data = $artifact ? json_decode($artifact->content, true) : null;

        return view('ai-agent.planning.mockups.show', [
            'project'     => $project,
            'screen'      => $screen,
            'artifact'    => $artifact,
            'data'        => $data,
            'config'      => $config,
            'prevScreen'  => $prevScreen,
            'nextScreen'  => $nextScreen,
            'pageTitle'   => "[{$screen->screen_id}] {$screen->title} 목업",
            'generateUrl' => route('ai-agent.projects.planning.mockups.generate', [$project, $screen]),
            'updateUrl'   => route('ai-agent.projects.planning.mockups.update', [$project, $screen]),
            'destroyUrl'  => route('ai-agent.projects.planning.mockups.destroy', [$project, $screen]),
            'previewUrl'  => $artifact ? route('ai-agent.projects.planning.mockups.preview', [$project, $screen]) : null,
            'standaloneUrl' => $artifact ? route('ai-agent.projects.planning.mockups.preview.standalone', [$project, $screen]) : null,
            'downloadUrl' => $artifact ? route('ai-agent.projects.planning.mockups.download', [$project, $screen]) : null,
            'historyUrl'  => $artifact ? route('ai-agent.projects.artifact.versions', [$project, $artifact]) : null,
            'indexUrl'    => route('ai-agent.projects.planning.mockups', $project),
            'csrfToken'   => csrf_token(),
        ]);
    }

    // ── 미리보기 (iframe용 HTML 반환) ─────────────────────────────────────────

    public function preview(Project $project, AiAgentScreen $screen): Response
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->firstOrFail();

        $data  = json_decode($artifact->content, true);
        $code  = $data['main_file']['content'] ?? '';
        $stack = ProjectAiAgentConfig::forProject($project->id)?->frontend_stack ?? FrontendStack::HTML;

        $html = match($stack) {
            FrontendStack::REACT => $this->wrapReactInHtml($code),
            FrontendStack::VUE   => $this->wrapVueInHtml($code),
            default              => $code,
        };

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    // ── 전체화면 미리보기 (새 탭) ──────────────────────────────────────────────

    public function previewStandalone(Project $project, AiAgentScreen $screen): Response
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->firstOrFail();

        $data  = json_decode($artifact->content, true);
        $code  = $data['main_file']['content'] ?? '';
        $stack = ProjectAiAgentConfig::forProject($project->id)?->frontend_stack ?? FrontendStack::HTML;

        $html = match($stack) {
            FrontendStack::REACT => $this->wrapReactInHtml($code),
            FrontendStack::VUE   => $this->wrapVueInHtml($code),
            default              => $code,
        };

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    // ── 코드 다운로드 ─────────────────────────────────────────────────────────

    public function download(Project $project, AiAgentScreen $screen): Response
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->firstOrFail();

        $data     = json_decode($artifact->content, true);
        $code     = $data['main_file']['content'] ?? '';
        $filename = $data['main_file']['name'] ?? ($screen->screen_id . '.html');

        return response($code)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    // ── 단일 생성 ─────────────────────────────────────────────────────────────

    public function generateOne(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        if ($this->wasRecentlyGenerated($screen)) {
            return response()->json(['success' => false, 'message' => '최근 ' . self::COOLDOWN_MINUTES . '분 내에 이미 생성되었습니다. 잠시 후 다시 시도하세요.'], 429);
        }

        $stage = $this->resolvePlanningStage($project);

        try {
            $result = $this->aiService->generateForScreen(
                projectId: $project->id,
                stageId:   $stage->id,
                screen:    $screen,
                userId:    (int) auth()->id(),
            );

            $data = json_decode($result['artifact']->content, true);

            return response()->json([
                'success'    => true,
                'code'       => $data['main_file']['content'] ?? '',
                'filename'   => $data['main_file']['name'] ?? '',
                'description' => $data['description'] ?? '',
                'features'   => $data['features'] ?? [],
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

        $validated = $request->validate(['code' => 'required|string|min:10']);

        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->firstOrFail();

        $data = json_decode($artifact->content, true) ?? [];
        $data['main_file']['content'] = $validated['code'];

        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited', 'edited_at' => now()->toIso8601String()],
        );

        $screen->update(['mockup_content' => $validated['code']]);

        return response()->json(['success' => true, 'version' => $artifact->fresh()->version]);
    }

    // ── 목업 삭제 ─────────────────────────────────────────────────────────────

    public function destroy(Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        abort_unless($screen->project_id === $project->id, 404);

        AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->delete();

        $screen->update(['mockup_content' => null]);

        return response()->json(['success' => true]);
    }

    // ── 일괄 생성 시작 ────────────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'only_missing'   => 'boolean',
            'screen_ids'     => 'nullable|array',
            'screen_ids.*'   => 'integer',
            'confirmed_cost' => 'boolean',
        ]);

        $onlyMissing = $validated['only_missing'] ?? true;

        $screens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->orderBy('order')
            ->get();

        if ($onlyMissing) {
            $existing = AiAgentArtifact::where('project_id', $project->id)
                ->where('type', ArtifactType::MOCKUP->value)
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

        // 비용 추정 확인
        if (!($validated['confirmed_cost'] ?? false)) {
            $estimatedCost = $screens->count() * self::ESTIMATED_COST_PER_SCREEN;
            return response()->json([
                'requires_confirmation' => true,
                'estimated_cost'        => round($estimatedCost, 2),
                'screen_count'          => $screens->count(),
            ]);
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
            'success'      => true,
            'session_id'   => $sessionId,
            'total_screens' => $screens->count(),
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

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '목업 일괄 생성을 시작합니다...', 'progress' => 2]);
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

    // ── iframe 래핑 헬퍼 ──────────────────────────────────────────────────────

    private function wrapReactInHtml(string $tsxCode): string
    {
        $escaped = htmlspecialchars($tsxCode, ENT_NOQUOTES, 'UTF-8', false);

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>React Preview</title>
<script src="https://cdn.tailwindcss.com"></script>
<script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body>
<div id="root"></div>
<script type="text/babel" data-presets="react,typescript">
{$tsxCode}
try {
  const RootComponent = (typeof exports !== 'undefined' && exports && exports.default)
    ? exports.default
    : (typeof App !== 'undefined' ? App : null);
  if (RootComponent) {
    ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(RootComponent));
  } else {
    document.getElementById('root').innerHTML = '<p style="padding:20px;color:#dc2626;">컴포넌트를 찾을 수 없습니다 (default export 또는 App 필요)</p>';
  }
} catch(e) {
  document.getElementById('root').innerHTML = '<pre style="padding:20px;color:#dc2626;">렌더링 오류: ' + e.message + '</pre>';
}
</script>
</body>
</html>
HTML;
    }

    private function wrapVueInHtml(string $vueCode): string
    {
        $parsed = $this->parseVueSfc($vueCode);

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vue Preview</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>
<body>
<div id="app">{$parsed['template']}</div>
<style>{$parsed['style']}</style>
<script>
const { createApp, ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } = Vue;
try {
  createApp({
    setup() {
      {$parsed['script']}
    }
  }).mount('#app');
} catch(e) {
  document.getElementById('app').innerHTML = '<pre style="padding:20px;color:#dc2626;">Vue 렌더링 오류: ' + e.message + '</pre>';
}
</script>
<div style="position:fixed;bottom:0;left:0;right:0;background:#fef3c7;padding:4px 12px;font-size:11px;color:#92400e;text-align:center;z-index:9999;">
  ⚠️ Vue SFC 미리보기는 근사치입니다. 정확한 결과는 Vue 프로젝트에서 확인하세요.
</div>
</body>
</html>
HTML;
    }

    private function parseVueSfc(string $vueCode): array
    {
        preg_match('/<template>(.*?)<\/template>/s', $vueCode, $tm);
        preg_match('/<script\s+setup[^>]*>(.*?)<\/script>/s', $vueCode, $ss);
        if (empty($ss[1])) {
            preg_match('/<script[^>]*>(.*?)<\/script>/s', $vueCode, $ss);
        }
        preg_match('/<style[^>]*>(.*?)<\/style>/s', $vueCode, $stm);

        $template = trim($tm[1] ?? '<p style="padding:20px">템플릿을 파싱할 수 없습니다.</p>');
        $style    = trim($stm[1] ?? '');

        $script = trim($ss[1] ?? '');
        // Remove TypeScript type annotations for browser compatibility
        $script = preg_replace('/:\s*(?:string|number|boolean|any|void|never|null|undefined|object)(?:\[\])?(?:\s*\|[^,;=\n\)]*)?(?=[,;=\)\s\n])/U', '', $script ?? '');
        $script = preg_replace('/<[A-Z]\w*(?:,\s*[A-Z]\w*)*>/U', '', $script ?? '');

        // For <script setup>, heuristic return all declared refs/computeds/functions
        if (!str_contains($script, 'return {') && !str_contains($script, 'return{')) {
            preg_match_all('/(?:const|let)\s+(\w+)\s*=\s*(?:ref|reactive|computed)\s*\(/', $script, $dm);
            preg_match_all('/function\s+(\w+)\s*\(/', $script, $fm);
            preg_match_all('/const\s+(\w+)\s*=\s*(?:async\s+)?\([^)]*\)\s*=>/', $script, $am);
            $all = array_unique(array_merge($dm[1] ?? [], $fm[1] ?? [], $am[1] ?? []));
            if (!empty($all)) {
                $script .= "\nreturn { " . implode(', ', $all) . " };";
            }
        }

        return ['template' => $template, 'script' => $script, 'style' => $style];
    }

    // ── 내부 헬퍼 ─────────────────────────────────────────────────────────────

    private function wasRecentlyGenerated(AiAgentScreen $screen): bool
    {
        $artifact = AiAgentArtifact::where('project_id', $screen->project_id)
            ->where('type', ArtifactType::MOCKUP->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screen->id)
            ->latest()
            ->first();

        if (!$artifact) return false;

        $meta        = $artifact->meta ?? [];
        $generatedAt = $meta['generated_at'] ?? null;

        if (!$generatedAt) return false;

        return now()->diffInMinutes(\Carbon\Carbon::parse($generatedAt)) < self::COOLDOWN_MINUTES;
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
