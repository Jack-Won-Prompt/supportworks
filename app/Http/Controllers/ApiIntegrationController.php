<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ApiCallExtractor;
use App\Services\Agent\ApiIntegrationService;
use App\Services\Agent\BackendEndpointExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Str;
use ZipArchive;

class ApiIntegrationController extends Controller
{
    public function __construct(
        private readonly ApiIntegrationService    $service,
        private readonly ApiCallExtractor         $callExtractor,
        private readonly BackendEndpointExtractor $endpointExtractor,
    ) {}

    // ── 메인 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $artifact = $this->getArtifact($project->id);
        $decoded  = $artifact ? (json_decode($artifact->content, true) ?? []) : null;
        $analysis = $decoded['analysis'] ?? null;
        $files    = $decoded['integration_files'] ?? null;

        // Pre-condition counts
        $feCount = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)->where('scope_type', 'screen')->count();
        $beCount = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::BACKEND_CODE->value)->where('scope_type', 'resource')->count();

        return view('ai-agent.dev.api-integration.index', [
            'project'      => $project,
            'artifact'     => $artifact,
            'analysis'     => $analysis,
            'files'        => $files,
            'feCount'      => $feCount,
            'beCount'      => $beCount,
            'analyzeUrl'   => route('ai-agent.projects.dev.api-connect.analyze', $project),
            'regenUrl'     => route('ai-agent.projects.dev.api-connect.regen-files', $project),
            'exportUrl'    => route('ai-agent.projects.dev.api-connect.export', $project),
            'pageTitle'    => 'API 연계 (T44)',
        ]);
    }

    // ── 분석 실행 ─────────────────────────────────────────────────────────────

    public function analyze(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $analysis  = $this->service->analyze($project->id);
            $intFiles  = $this->service->generateIntegrationFiles($project->id);
            $artifact  = $this->service->persistResult($project->id, $analysis, $intFiles, (int) auth()->id());

            return response()->json([
                'success'    => true,
                'artifact_id'=> $artifact->id,
                'version'    => $artifact->version,
                'metadata'   => $analysis['$metadata'],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── JSON 미리보기 ──────────────────────────────────────────────────────────

    public function preview(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['error' => '분석 결과가 없습니다. 먼저 분석을 실행하세요.'], 404);
        }

        return response()->json(json_decode($artifact->content, true));
    }

    // ── 통합 파일 재생성 ──────────────────────────────────────────────────────

    public function regenFiles(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $artifact = $this->getArtifact($project->id);
            if (!$artifact) {
                return response()->json(['success' => false, 'message' => '먼저 분석을 실행하세요.'], 404);
            }

            $decoded  = json_decode($artifact->content, true) ?? [];
            $analysis = $decoded['analysis'] ?? [];
            $intFiles = $this->service->generateIntegrationFiles($project->id);

            $artifact->updateWithVersion(
                content: json_encode(array_merge($decoded, ['integration_files' => $intFiles]),
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                userId: (int) auth()->id(),
                meta:   array_merge($artifact->meta ?? [], ['files_regenerated_at' => now()->toIso8601String()]),
            );

            return response()->json(['success' => true, 'files_count' => count($intFiles)]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── ZIP 다운로드 ───────────────────────────────────────────────────────────

    public function export(Project $project): Response
    {
        $this->authorizeProject($project);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) abort(404, '통합 파일이 없습니다. 먼저 분석을 실행하세요.');

        $decoded  = json_decode($artifact->content, true) ?? [];
        $files    = $decoded['integration_files'] ?? [];
        $analysis = $decoded['analysis'] ?? [];

        // Add analysis report as markdown
        $files['api-integration-report.md'] = $this->buildMarkdownReport($analysis);

        $tmpFile = tempnam(sys_get_temp_dir(), 'api_int_');
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();
        $data = file_get_contents($tmpFile);
        unlink($tmpFile);

        $slug = Str::slug($project->name);
        $name = "{$slug}-api-integration-" . now()->format('Ymd') . '.zip';

        return response($data, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function getArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_INTEGRATION->value)
            ->where('scope_type', 'project')
            ->latest('id')->first();
    }

    private function buildMarkdownReport(array $analysis): string
    {
        $meta = $analysis['$metadata'] ?? [];
        $lines = [
            '# API 연계 분석 리포트',
            '',
            '## 요약',
            '',
            "| 항목 | 수치 |",
            "|---|---|",
            "| Frontend API 호출 | {$meta['frontend_calls']}건 |",
            "| Backend 엔드포인트 | {$meta['backend_endpoints']}건 |",
            "| 매칭됨 | {$meta['matched']}쌍 |",
            "| 매칭 안 된 Frontend | {$meta['unmatched_frontend']}건 |",
            "| 매칭 안 된 Backend | {$meta['unmatched_backend']}건 |",
            "| 매칭률 | {$meta['compliance_rate']}% |",
            '',
            '## 매칭 목록',
            '',
        ];

        foreach ($analysis['matches'] ?? [] as $m) {
            $fe = $m['frontend_call'];
            $be = $m['backend_endpoint'];
            $lines[] = "- ✅ **{$fe['method']} {$fe['url']}** → `{$be['controller']}`";
            if (!empty($fe['screen_id'])) {
                $lines[] = "  - FE: [{$fe['screen_id']}] `{$fe['file']}:{$fe['line']}`";
            }
        }

        if (!empty($analysis['unmatched_frontend'])) {
            $lines[] = '';
            $lines[] = '## 매칭 안 된 Frontend 호출 ⚠️';
            $lines[] = '';
            foreach ($analysis['unmatched_frontend'] as $u) {
                $fe = $u['frontend_call'];
                $lines[] = "- ⚠️ **{$fe['method']} {$fe['url']}** — {$u['issue']}";
                $lines[] = "  - 제안: {$u['suggestion']}";
            }
        }

        if (!empty($analysis['unmatched_backend'])) {
            $lines[] = '';
            $lines[] = '## 매칭 안 된 Backend 엔드포인트 ℹ️';
            $lines[] = '';
            foreach ($analysis['unmatched_backend'] as $u) {
                $be = $u['backend_endpoint'];
                $lines[] = "- ℹ️ **{$be['method']} {$be['uri']}** (`{$be['resource']}`) — {$u['issue']}";
            }
        }

        $lines[] = '';
        $lines[] = "---";
        $lines[] = "_생성: " . now()->format('Y-m-d H:i:s') . "_";

        return implode("\n", $lines);
    }

    private function authorizeProject(Project $project): void
    {
        $userId = (int) auth()->id();
        if (!ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists()
            && $project->created_by !== $userId) {
            abort(403);
        }
    }
}
