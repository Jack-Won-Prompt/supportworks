<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ReleasePackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReleasePackageController extends Controller
{
    public function __construct(
        private readonly ReleasePackageService $service,
    ) {}

    // ── Main page ─────────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $prereqs  = $this->service->checkPrerequisites($project->id);
        $existing = $this->service->loadExisting($project->id);

        $preview = null;
        if ($existing && $existing['exists']) {
            $preview = $this->service->previewStructure($existing['path']);
        }

        return view('ai-agent.release.package.index', [
            'project'   => $project,
            'prereqs'   => $prereqs,
            'existing'  => $existing,
            'preview'   => $preview,
            'pageTitle' => '통합 릴리즈 패키지',
        ]);
    }

    // ── Generate ──────────────────────────────────────────────────────────────

    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $prereqs = $this->service->checkPrerequisites($project->id);
        if (!$prereqs['can_build']) {
            return response()->json([
                'success' => false,
                'message' => 'Phase 1-4가 모두 승인되어야 패키지를 생성할 수 있습니다.',
            ], 422);
        }

        try {
            $zipPath = $this->service->generatePackage($project, auth()->user());
            $size    = file_exists($zipPath) ? filesize($zipPath) : 0;
            $sizeMb  = round($size / 1024 / 1024, 2);

            return response()->json([
                'success'   => true,
                'size_mb'   => $sizeMb,
                'size_bytes'=> $size,
                'message'   => "패키지 생성 완료 ({$sizeMb} MB)",
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json([
                'success' => false,
                'message' => '패키지 생성 실패: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Download ──────────────────────────────────────────────────────────────

    public function download(Project $project): StreamedResponse|Response
    {
        $this->authorizeProject($project);

        $existing = $this->service->loadExisting($project->id);

        if (!$existing || !$existing['exists']) {
            return response('패키지가 없습니다. 먼저 생성하세요.', 404);
        }

        $path = $existing['path'];
        $name = basename($path);

        return response()->streamDownload(function () use ($path) {
            $handle = fopen($path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 65536);
                flush();
            }
            fclose($handle);
        }, $name, [
            'Content-Type'   => 'application/zip',
            'Content-Length' => filesize($path),
        ]);
    }

    // ── Manifest JSON ─────────────────────────────────────────────────────────

    public function manifest(Project $project): JsonResponse|Response
    {
        $this->authorizeProject($project);

        $existing = $this->service->loadExisting($project->id);

        if (!$existing) {
            return response()->json(['error' => '패키지가 없습니다.'], 404);
        }

        return response()->json($existing['manifest'] ?? []);
    }

    // ── Preview ───────────────────────────────────────────────────────────────

    public function preview(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $existing = $this->service->loadExisting($project->id);

        if (!$existing || !$existing['exists']) {
            return response()->json(['nodes' => []]);
        }

        $nodes = $this->service->previewStructure($existing['path']);

        return response()->json([
            'nodes'   => $nodes,
            'path'    => basename($existing['path']),
            'size_mb' => round($existing['size'] / 1024 / 1024, 2),
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $this->service->deletePackage($project->id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
