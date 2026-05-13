<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\BackendCodeAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BackendCodeController extends Controller
{
    private const CACHE_PREFIX    = 'ai-agent:backend-code:batch:';
    private const CACHE_TTL       = 3600;
    private const COST_PER_RESOURCE = 0.65;

    public function __construct(
        private readonly BackendCodeAiService $service,
    ) {}

    // ── 목록 ─────────────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $resources = $this->service->getResources($project->id);

        $existingArtifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->get()->keyBy('scope_id');

        $erdArtifact  = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::ERD->value)->latest('id')->first();
        $apiArtifact  = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::API_SPEC->value)->latest('id')->first();
        $rbacArtifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::RBAC_MODEL->value)->latest('id')->first();

        $resourceData = collect($resources)->map(function ($res) use ($existingArtifacts, $project) {
            $scopeId  = $this->service->getScopeId($res['table']);
            $artifact = $existingArtifacts->get($scopeId);
            $decoded  = $artifact ? json_decode($artifact->content, true) : null;
            return [
                'resource'      => $res['name'],
                'table'         => $res['table'],
                'description'   => $res['description'],
                'scope_id'      => $scopeId,
                'artifact'      => $artifact,
                'has_code'      => $artifact !== null,
                'files_count'   => count($decoded['files'] ?? []),
                'routes_count'  => count($decoded['routes'] ?? []),
                'generated_at'  => $artifact?->meta['generated_at'] ?? null,
                'version'       => $artifact?->version ?? null,
                'cost_usd'      => $artifact?->meta['cost_usd'] ?? null,
                'show_url'      => route('ai-agent.projects.dev.backend.show', [$project, $res['table']]),
                'generate_url'  => route('ai-agent.projects.dev.backend.resource.generate', [$project, $res['table']]),
            ];
        });

        $totalCount    = count($resources);
        $doneCount     = $existingArtifacts->count();
        $missingCount  = $totalCount - $doneCount;
        $estimatedCost = round($missingCount * self::COST_PER_RESOURCE, 2);

        return view('ai-agent.dev.backend-code.index', [
            'project'         => $project,
            'resourceData'    => $resourceData,
            'totalCount'      => $totalCount,
            'doneCount'       => $doneCount,
            'missingCount'    => $missingCount,
            'estimatedCost'   => $estimatedCost,
            'hasErd'          => $erdArtifact !== null,
            'hasApi'          => $apiArtifact !== null,
            'hasRbac'         => $rbacArtifact !== null,
            'batchStartUrl'   => route('ai-agent.projects.dev.backend.batch.start', $project),
            'batchSseUrlTpl'  => route('ai-agent.projects.dev.backend.batch.sse', [$project, 'SESSION_ID']),
            'downloadAllUrl'  => route('ai-agent.projects.dev.backend.download-all', $project),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'       => 'Backend 코드 생성 (T43)',
        ]);
    }

    // ── 배치 시작 ────────────────────────────────────────────────────────────

    public function batchStart(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'table_names'    => 'nullable|array',
            'table_names.*'  => 'string',
            'only_missing'   => 'boolean',
            'confirmed_cost' => 'boolean',
        ]);

        $onlyMissing   = (bool) ($validated['only_missing']   ?? false);
        $confirmedCost = (bool) ($validated['confirmed_cost'] ?? false);

        if (!$confirmedCost) {
            $resources = $this->service->getResources($project->id);
            $count     = isset($validated['table_names'])
                ? count($validated['table_names'])
                : count($resources);

            if ($onlyMissing) {
                $existing = AiAgentArtifact::where('project_id', $project->id)
                    ->where('type', ArtifactType::BACKEND_CODE->value)
                    ->where('scope_type', 'resource')->count();
                $count = max(0, $count - $existing);
            }
            $estimated = round($count * self::COST_PER_RESOURCE, 2);

            return response()->json([
                'requiresConfirmation' => true,
                'resourceCount'        => $count,
                'estimatedCost'        => $estimated,
                'warning'              => $estimated > 5 ? 'COST_HIGH' : null,
            ]);
        }

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id'   => $project->id,
            'user_id'      => (int) auth()->id(),
            'table_names'  => $validated['table_names'] ?? null,
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
                    tableNames:  $session['table_names'],
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

    // ── 단일 리소스 상세 ─────────────────────────────────────────────────────

    public function show(Project $project, string $resource): View
    {
        $this->authorizeProject($project);

        $scopeId  = $this->service->getScopeId($resource);
        $artifact = $this->getArtifact($project->id, $scopeId);
        $decoded  = $artifact ? (json_decode($artifact->content, true) ?? []) : null;

        $context  = $this->service->buildContext($project->id, $resource);

        return view('ai-agent.dev.backend-code.show', [
            'project'       => $project,
            'tableName'     => $resource,
            'resourceName'  => $decoded ? ($decoded['$metadata']['resource'] ?? $resource) : $resource,
            'artifact'      => $artifact,
            'decoded'       => $decoded,
            'hasCode'       => $artifact !== null,
            'context'       => $context,
            'generateUrl'   => route('ai-agent.projects.dev.backend.resource.generate', [$project, $resource]),
            'updateFileUrl' => route('ai-agent.projects.dev.backend.resource.files.update', [$project, $resource]),
            'downloadUrl'   => route('ai-agent.projects.dev.backend.resource.download', [$project, $resource]),
            'destroyUrl'    => route('ai-agent.projects.dev.backend.resource.destroy', [$project, $resource]),
            'indexUrl'      => route('ai-agent.projects.dev.backend', $project),
            'historyUrl'    => $artifact
                ? route('ai-agent.projects.artifact.versions', [$project, $artifact])
                : null,
            'pageTitle'     => "[{$resource}] Backend Code — T43",
        ]);
    }

    // ── 단일 생성 ────────────────────────────────────────────────────────────

    public function generateForResource(Request $request, Project $project, string $resource): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $result   = $this->service->generateForResource(
                projectId: $project->id,
                tableName: $resource,
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
                'routes'      => $decoded['routes'] ?? [],
                'todo_items'  => $decoded['todo_items'] ?? [],
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

    // ── 파일 편집 저장 ───────────────────────────────────────────────────────

    public function updateFile(Request $request, Project $project, string $resource): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'path'    => 'required|string|max:200',
            'content' => 'required|string',
        ]);

        $scopeId  = $this->service->getScopeId($resource);
        $artifact = $this->getArtifact($project->id, $scopeId);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '산출물을 찾을 수 없습니다.'], 404);
        }

        $decoded = json_decode($artifact->content, true) ?? [];
        $found   = false;

        foreach ($decoded['files'] as &$file) {
            if ($file['path'] === $validated['path']) {
                $file['content'] = $validated['content'];
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

    // ── 삭제 ─────────────────────────────────────────────────────────────────

    public function destroy(Project $project, string $resource): JsonResponse
    {
        $this->authorizeProject($project);

        $scopeId  = $this->service->getScopeId($resource);
        $artifact = $this->getArtifact($project->id, $scopeId);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->delete();
        return response()->json(['success' => true]);
    }

    // ── 단일 zip 다운로드 ────────────────────────────────────────────────────

    public function download(Project $project, string $resource): Response
    {
        $this->authorizeProject($project);

        $scopeId  = $this->service->getScopeId($resource);
        $artifact = $this->getArtifact($project->id, $scopeId);
        if (!$artifact) abort(404, '코드 산출물이 없습니다.');

        $decoded  = json_decode($artifact->content, true) ?? [];
        $files    = $decoded['files'] ?? [];
        $resName  = $decoded['$metadata']['resource'] ?? $resource;

        $allFiles = [];
        foreach ($files as $file) {
            $allFiles[$file['path']] = $file['content'];
        }

        $zip  = $this->buildRawZip($allFiles);
        $name = Str::slug($resName) . '-backend-' . now()->format('Ymd') . '.zip';

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
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->get();

        if ($artifacts->isEmpty()) abort(404, '생성된 코드가 없습니다.');

        $allFiles = [];
        foreach ($artifacts as $artifact) {
            $decoded  = json_decode($artifact->content, true) ?? [];
            $resName  = $decoded['$metadata']['resource'] ?? 'resource';
            foreach ($decoded['files'] ?? [] as $file) {
                $allFiles[$file['path']] = $file['content'];
            }
        }

        $zip  = $this->buildRawZip($allFiles);
        $slug = Str::slug($project->name);
        $name = "{$slug}-backend-code-" . now()->format('Ymd') . '.zip';

        return response($zip, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function getArtifact(int $projectId, int $scopeId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->where('scope_id', $scopeId)
            ->latest('id')->first();
    }

    private function buildRawZip(array $files): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'bc_zip_');
        $zip     = new ZipArchive();
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
