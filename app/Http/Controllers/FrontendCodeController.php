<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\FrontendCodeAiService;
use App\Services\Agent\FrontendCodePreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class FrontendCodeController extends Controller
{
    private const CACHE_PREFIX   = 'ai-agent:frontend-code:batch:';
    private const CACHE_TTL      = 3600;
    private const COST_PER_SCREEN = 0.80;

    public function __construct(
        private readonly FrontendCodeAiService     $service,
        private readonly FrontendCodePreviewService $preview,
    ) {}

    // ── 목록 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $stack   = $this->service->resolveStack($project->id);
        $screens = AiAgentScreen::where('project_id', $project->id)
            ->active()->orderBy('order')->get();

        $existingArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->get()->keyBy('scope_id');

        $codePromptArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_GEN_PROMPT->value)
            ->where('scope_type', 'screen')
            ->get()->keyBy('scope_id');

        $screenData = $screens->map(function ($screen) use ($existingArtifacts, $codePromptArtifacts, $project) {
            $artifact   = $existingArtifacts->get($screen->id);
            $codePrompt = $codePromptArtifacts->get($screen->id);
            $decoded    = $artifact ? json_decode($artifact->content, true) : null;
            return [
                'screen'         => $screen,
                'artifact'       => $artifact,
                'has_code'       => $artifact !== null,
                'has_prompt'     => $codePrompt !== null,
                'files_count'    => count($decoded['files'] ?? []),
                'generated_at'   => $artifact?->meta['generated_at'] ?? null,
                'show_url'       => route('ai-agent.projects.dev.frontend-code.show', [$project, $screen]),
                'generate_url'   => route('ai-agent.projects.dev.frontend-code.screen.generate', [$project, $screen]),
            ];
        });

        $totalCount   = $screens->count();
        $doneCount    = $existingArtifacts->count();
        $promptCount  = $codePromptArtifacts->count();
        $missingCount = $totalCount - $doneCount;
        $estimatedCost = round($totalCount * self::COST_PER_SCREEN, 2);

        return view('ai-agent.dev-prep.frontend-code.index', [
            'project'         => $project,
            'stack'           => $stack,
            'screenData'      => $screenData,
            'totalCount'      => $totalCount,
            'doneCount'       => $doneCount,
            'promptCount'     => $promptCount,
            'missingCount'    => $missingCount,
            'estimatedCost'   => $estimatedCost,
            'batchStartUrl'   => route('ai-agent.projects.dev.frontend-code.batch.start', $project),
            'batchSseUrlTpl'  => route('ai-agent.projects.dev.frontend-code.batch.sse', [$project, 'SESSION_ID']),
            'downloadAllUrl'  => route('ai-agent.projects.dev.frontend-code.download-all', $project),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'       => 'Frontend Code 생성 (T40)',
        ]);
    }

    // ── 배치 시작 ────────────────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'screen_ids'     => 'nullable|array',
            'screen_ids.*'   => 'integer',
            'only_missing'   => 'boolean',
            'confirmed_cost' => 'boolean',
        ]);

        $onlyMissing   = (bool) ($validated['only_missing']   ?? false);
        $confirmedCost = (bool) ($validated['confirmed_cost'] ?? false);

        // 비용 확인 단계
        if (!$confirmedCost) {
            $count = isset($validated['screen_ids'])
                ? count($validated['screen_ids'])
                : AiAgentScreen::where('project_id', $project->id)->active()->count();
            if ($onlyMissing) {
                $existing = AiAgentArtifact::where('project_id', $project->id)
                    ->where('type', ArtifactType::FRONTEND_CODE->value)
                    ->where('scope_type', 'screen')->count();
                $count = max(0, $count - $existing);
            }
            $estimated = round($count * self::COST_PER_SCREEN, 2);

            return response()->json([
                'requiresConfirmation' => true,
                'screenCount'          => $count,
                'estimatedCost'        => $estimated,
                'warning'              => $estimated > 5 ? 'COST_HIGH' : null,
            ]);
        }

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id'   => $project->id,
            'user_id'      => (int) auth()->id(),
            'screen_ids'   => $validated['screen_ids'] ?? null,
            'only_missing' => $onlyMissing,
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ── 배치 SSE ─────────────────────────────────────────────────────────────

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

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => '코드 생성을 시작합니다...', 'progress' => 0]);
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

    // ── 단일 화면 상세 ───────────────────────────────────────────────────────

    public function show(Project $project, AiAgentScreen $screen): View
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $stack    = $this->service->resolveStack($project->id);
        $artifact = $this->getArtifact($project->id, $screen->id);
        $decoded  = $artifact ? (json_decode($artifact->content, true) ?? []) : null;

        return view('ai-agent.dev-prep.frontend-code.show', [
            'project'       => $project,
            'screen'        => $screen,
            'stack'         => $stack,
            'artifact'      => $artifact,
            'decoded'       => $decoded,
            'hasCode'       => $artifact !== null,
            'generateUrl'   => route('ai-agent.projects.dev.frontend-code.screen.generate', [$project, $screen]),
            'previewUrl'    => route('ai-agent.projects.dev.frontend-code.screen.preview', [$project, $screen]),
            'downloadUrl'   => route('ai-agent.projects.dev.frontend-code.screen.download', [$project, $screen]),
            'updateFileUrl' => route('ai-agent.projects.dev.frontend-code.screen.files.update', [$project, $screen]),
            'destroyUrl'    => route('ai-agent.projects.dev.frontend-code.screen.destroy', [$project, $screen]),
            'indexUrl'      => route('ai-agent.projects.dev.frontend-code', $project),
            'historyUrl'    => $artifact
                ? route('ai-agent.projects.artifact.versions', [$project, $artifact])
                : null,
            'pageTitle'     => "[{$screen->screen_id}] {$screen->title} — Frontend Code",
        ]);
    }

    // ── 단일 생성 ────────────────────────────────────────────────────────────

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
            $decoded  = json_decode($artifact->content, true);

            return response()->json([
                'success'     => true,
                'artifact_id' => $artifact->id,
                'version'     => $artifact->version,
                'files_count' => $result['files_count'],
                'files'       => $decoded['files'] ?? [],
                'dependencies'=> $decoded['dependencies'] ?? [],
                'todo_items'  => $decoded['todo_items'] ?? [],
                'notes'       => $decoded['implementation_notes'] ?? [],
                'tokens_in'   => $result['tokens_in'],
                'tokens_out'  => $result['tokens_out'],
                'cost_usd'    => round($result['cost'], 4),
                'model'       => $result['model'],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── iframe 미리보기 ──────────────────────────────────────────────────────

    public function preview(Project $project, AiAgentScreen $screen): Response
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $artifact = $this->getArtifact($project->id, $screen->id);
        if (!$artifact) {
            return response('<html><body style="padding:24px;font-family:system-ui"><p style="color:#d97706">코드가 아직 생성되지 않았습니다.</p></body></html>', 200)
                ->header('Content-Type', 'text/html');
        }

        $decoded  = json_decode($artifact->content, true) ?? [];
        $html     = $this->preview->buildPreviewHtml($decoded);

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    // ── 단일 zip 다운로드 ────────────────────────────────────────────────────

    public function download(Project $project, AiAgentScreen $screen): Response
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $artifact = $this->getArtifact($project->id, $screen->id);
        if (!$artifact) abort(404, '코드 산출물이 없습니다.');

        $decoded  = json_decode($artifact->content, true) ?? [];
        $files    = $decoded['files'] ?? [];
        $stack    = $decoded['$metadata']['stack'] ?? 'code';

        $zip  = $this->buildZip([$screen->screen_id => $files], $decoded['dependencies'] ?? []);
        $name = Str::slug($screen->screen_id . '-' . $screen->title) . "-{$stack}-" . now()->format('Ymd') . '.zip';

        return response($zip, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── 전체 zip 다운로드 ────────────────────────────────────────────────────

    public function downloadAll(Project $project): Response
    {
        $this->authorizeProject($project);

        $artifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->get();

        if ($artifacts->isEmpty()) abort(404, '생성된 코드가 없습니다.');

        $allFiles    = [];
        $allDeps     = [];
        foreach ($artifacts as $artifact) {
            $decoded = json_decode($artifact->content, true) ?? [];
            $sid     = $decoded['$metadata']['screen_id'] ?? "artifact_{$artifact->id}";
            foreach ($decoded['files'] ?? [] as $file) {
                $allFiles["{$sid}/{$file['path']}"] = $file['content'];
            }
            foreach ($decoded['dependencies'] ?? [] as $dep) {
                $allDeps[$dep['name']] = $dep;
            }
        }

        $zip  = $this->buildRawZip($allFiles);
        $slug = Str::slug($project->name);
        $name = "{$slug}-frontend-code-" . now()->format('Ymd') . '.zip';

        return response($zip, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── 파일 편집 저장 ───────────────────────────────────────────────────────

    public function updateFile(Request $request, Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeScreen($project, $screen);

        $validated = $request->validate([
            'path'    => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        $artifact = $this->getArtifact($project->id, $screen->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '산출물을 찾을 수 없습니다.'], 404);
        }

        $decoded = json_decode($artifact->content, true) ?? [];
        $found   = false;

        foreach ($decoded['files'] as &$file) {
            if ($file['path'] === $validated['path']) {
                $file['content'] = $validated['content'];
                $file['lines']   = substr_count($validated['content'], "\n") + 1;
                $found           = true;
                break;
            }
        }
        unset($file);

        if (!$found) {
            return response()->json(['success' => false, 'message' => "파일 '{$validated['path']}'를 찾을 수 없습니다."], 404);
        }

        $artifact->updateWithVersion(
            content: json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'file_edited', 'file' => $validated['path'], 'edited_at' => now()->toIso8601String()],
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
            return response()->json(['success' => false, 'message' => '산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->delete();
        return response()->json(['success' => true]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getArtifact(int $projectId, int $screenId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->where('scope_id', $screenId)
            ->latest('id')->first();
    }

    private function buildZip(array $screenFilesMap, array $deps): string
    {
        $allFiles = [];
        foreach ($screenFilesMap as $screenId => $files) {
            foreach ($files as $file) {
                $allFiles["{$screenId}/{$file['path']}"] = $file['content'];
            }
        }
        if (!empty($deps)) {
            $pkgDeps = [];
            foreach ($deps as $dep) {
                $pkgDeps[$dep['name']] = $dep['version'] ?? '*';
            }
            $allFiles['package.json'] = json_encode(['dependencies' => $pkgDeps], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        return $this->buildRawZip($allFiles);
    }

    private function buildRawZip(array $files): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'fc_zip_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();
        $data = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $data;
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
