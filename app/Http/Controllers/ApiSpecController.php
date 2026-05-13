<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ApiSpecAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiSpecController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:api-spec:sse:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly ApiSpecAiService $apiSpecService,
    ) {}

    // ── 메인 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $context  = $this->apiSpecService->buildContext($project->id);
        $artifact = $this->getArtifact($project->id);

        $specData     = null;
        $hasSpec      = false;
        if ($artifact && !empty($artifact->content)) {
            $specData = json_decode($artifact->content, true);
            $hasSpec  = !empty($specData['spec']['paths']);
        }

        $spec           = $specData['spec'] ?? null;
        $endpointsCount = 0;
        $schemasCount   = 0;
        if ($spec) {
            foreach ($spec['paths'] ?? [] as $methods) {
                $endpointsCount += count($methods);
            }
            $schemasCount = count($spec['components']['schemas'] ?? []);
        }

        $historyUrl = $artifact
            ? route('ai-agent.projects.artifact.versions', [$project, $artifact])
            : null;

        return view('ai-agent.dev-prep.api-spec.index', [
            'project'         => $project,
            'artifact'        => $artifact,
            'specData'        => $specData,
            'hasSpec'         => $hasSpec,
            'context'         => $context,
            'hasErd'          => (bool) $context['erd_artifact_id'],
            'screenCount'     => $context['screen_count'],
            'reqCount'        => $context['requirements_count'],
            'endpointsCount'  => $endpointsCount,
            'schemasCount'    => $schemasCount,
            'historyUrl'      => $historyUrl,
            'startUrl'        => route('ai-agent.projects.pre-dev.api-spec.generate.start', $project),
            'sseUrlTpl'       => route('ai-agent.projects.pre-dev.api-spec.generate.sse', [$project, 'SESSION_ID']),
            'saveUrl'         => route('ai-agent.projects.pre-dev.api-spec.save', $project),
            'exportUrl'       => route('ai-agent.projects.pre-dev.api-spec.export', $project),
            'regenerateUrl'   => route('ai-agent.projects.pre-dev.api-spec.regenerate', $project),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'       => 'API 명세서 — OpenAPI 3.0',
        ]);
    }

    // ── 생성 시작 ────────────────────────────────────────────────────────────

    public function generateStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id' => $project->id,
            'user_id'    => (int) auth()->id(),
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
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

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => 'API 명세서 생성을 시작합니다...', 'progress' => 5]);
            $startedAt = microtime(true);
            Cache::forget(self::CACHE_PREFIX . $sessionId);

            try {
                $result   = $this->apiSpecService->generate(
                    projectId:  $project->id,
                    userId:     $session['user_id'],
                    onProgress: function (array $progress) use ($startedAt) {
                        $this->sseEvent('progress', array_merge($progress, [
                            'elapsed' => round(microtime(true) - $startedAt, 1),
                        ]));
                    },
                );

                $artifact = $result['artifact'];
                $specData = json_decode($artifact->content, true);
                $spec     = $specData['spec'] ?? [];

                $endpointsCount = 0;
                foreach ($spec['paths'] ?? [] as $methods) {
                    $endpointsCount += count($methods);
                }

                $this->sseEvent('complete', [
                    'status'          => 'COMPLETED',
                    'endpoints_count' => $endpointsCount,
                    'schemas_count'   => $result['schemas_count'],
                    'tokens_in'       => $result['tokens_in'],
                    'tokens_out'      => $result['tokens_out'],
                    'cost_usd'        => round($result['cost'], 4),
                    'model'           => $result['model'],
                    'elapsed'         => round(microtime(true) - $startedAt, 2),
                    'artifact_id'     => $artifact->id,
                    'version'         => $artifact->version,
                    'spec'            => $spec,
                    'design_notes'    => $specData['design_notes'] ?? '',
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
            'content' => 'required|string|min:2',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'API 명세서 산출물을 찾을 수 없습니다.'], 404);
        }

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

        $format   = $request->query('format', 'yaml');
        $artifact = $this->getArtifact($project->id);

        if (!$artifact || empty($artifact->content)) {
            abort(404, 'API 명세서 산출물이 없습니다.');
        }

        $specData = json_decode($artifact->content, true) ?? [];
        $spec     = $specData['spec'] ?? [];
        $slug     = Str::slug($project->name);
        $date     = now()->format('Ymd');

        return match ($format) {
            'json'  => $this->exportJson($spec, $slug, $date),
            default => $this->exportYaml($spec, $slug, $date),
        };
    }

    // ── 재생성 ───────────────────────────────────────────────────────────────

    public function regenerate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $result   = $this->apiSpecService->generate(
                projectId:  $project->id,
                userId:     (int) auth()->id(),
                onProgress: fn() => null,
            );

            $artifact = $result['artifact'];
            $specData = json_decode($artifact->content, true);
            $spec     = $specData['spec'] ?? [];

            $endpointsCount = 0;
            foreach ($spec['paths'] ?? [] as $methods) {
                $endpointsCount += count($methods);
            }

            return response()->json([
                'success'         => true,
                'version'         => $artifact->version,
                'endpoints_count' => $endpointsCount,
                'schemas_count'   => $result['schemas_count'],
                'spec'            => $spec,
                'design_notes'    => $specData['design_notes'] ?? '',
                'model'           => $result['model'],
                'cost_usd'        => round($result['cost'], 4),
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Export helpers ────────────────────────────────────────────────────────

    private function exportYaml(array $spec, string $slug, string $date): \Illuminate\Http\Response
    {
        $yaml     = "# API 명세서 (OpenAPI 3.0)\n# 생성일: " . now()->format('Y-m-d') . "\n\n"
                  . $this->arrayToYaml($spec);
        $filename = "{$slug}-API-Spec-{$date}.yaml";

        return response($yaml, 200, [
            'Content-Type'        => 'text/yaml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportJson(array $spec, string $slug, string $date): \Illuminate\Http\Response
    {
        $json     = json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = "{$slug}-API-Spec-{$date}.json";

        return response($json, 200, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml   = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            $k = $this->yamlKey((string) $key);

            if (is_array($value)) {
                if (empty($value)) {
                    $yaml .= "{$spaces}{$k}: {}\n";
                } elseif (array_is_list($value)) {
                    $yaml .= "{$spaces}{$k}:\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $firstEntry = true;
                            foreach ($item as $ik => $iv) {
                                $ik2 = $this->yamlKey((string) $ik);
                                if ($firstEntry) {
                                    if (is_array($iv)) {
                                        $yaml .= "{$spaces}- {$ik2}:\n";
                                        $yaml .= $this->arrayToYaml($iv, $indent + 2);
                                    } else {
                                        $yaml .= "{$spaces}- {$ik2}: " . $this->yamlScalar($iv) . "\n";
                                    }
                                    $firstEntry = false;
                                } else {
                                    if (is_array($iv)) {
                                        $yaml .= "{$spaces}  {$ik2}:\n";
                                        $yaml .= $this->arrayToYaml($iv, $indent + 2);
                                    } else {
                                        $yaml .= "{$spaces}  {$ik2}: " . $this->yamlScalar($iv) . "\n";
                                    }
                                }
                            }
                        } else {
                            $yaml .= "{$spaces}- " . $this->yamlScalar($item) . "\n";
                        }
                    }
                } else {
                    $yaml .= "{$spaces}{$k}:\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= "{$spaces}{$k}: " . $this->yamlScalar($value) . "\n";
            }
        }

        return $yaml;
    }

    private function yamlKey(string $key): string
    {
        if (preg_match('/[\/\{\}\$\:\#\@\!\*\&\|\>\<\[\]]/', $key) || str_contains($key, ' ')) {
            return '"' . str_replace('"', '\\"', $key) . '"';
        }
        return $key;
    }

    private function yamlScalar(mixed $value): string
    {
        if (is_bool($value))  return $value ? 'true' : 'false';
        if (is_null($value))  return 'null';
        if (is_int($value) || is_float($value)) return (string) $value;
        $str = (string) $value;
        if ($str === '') return '""';
        if (preg_match('/[\:\#\{\}\[\]\,\&\*\?\|\>\!\%\@\`]/', $str)
            || str_contains($str, "\n")
            || str_starts_with($str, '"')
            || str_starts_with($str, "'")
            || preg_match('/^\d/', $str) && !is_numeric($str)) {
            return '"' . str_replace('\\', '\\\\', str_replace('"', '\\"', str_replace("\n", '\n', $str))) . '"';
        }
        return $str;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_SPEC->value)
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
