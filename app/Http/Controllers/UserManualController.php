<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Agent\UserManualDataContext;
use App\Services\Agent\UserManualService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManualController extends Controller
{
    public function __construct(
        private readonly UserManualService     $service,
        private readonly UserManualDataContext $dataContext,
    ) {}

    public function index(Project $project): View
    {
        $artifact = $this->service->loadExisting($project->id);
        $context  = $this->dataContext->build($project->id);

        return view('ai-agent.release.user-manual.index', [
            'artifact' => $artifact,
            'stats'    => $context['stats'],
            'roles'    => $context['roles'],
        ]);
    }

    public function generate(Request $request, Project $project): JsonResponse
    {
        try {
            $artifact = $this->service->generate($project, $request->user());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success'      => true,
            'generated_at' => $artifact->created_at->format('Y-m-d H:i'),
            'title'        => $artifact->title,
        ]);
    }

    public function preview(Project $project): JsonResponse
    {
        $artifact = $this->service->loadExisting($project->id);

        if (!$artifact) {
            return response()->json(['error' => '매뉴얼이 없습니다.'], 404);
        }

        return response()->json([
            'content'      => $artifact->content,
            'generated_at' => $artifact->created_at->format('Y-m-d H:i'),
        ]);
    }

    public function export(Request $request, Project $project)
    {
        $format = $request->query('format', 'md');
        $slug   = Str::slug($project->name) ?: 'project';

        if ($format === 'zip') {
            try {
                $zipPath = $this->service->generatePackage($project, $request->user());
            } catch (\Throwable $e) {
                abort(500, $e->getMessage());
            }

            return response()->streamDownload(function () use ($zipPath) {
                $fp = fopen($zipPath, 'rb');
                while (!feof($fp)) {
                    echo fread($fp, 65536);
                    flush();
                }
                fclose($fp);
            }, "user-manual-{$slug}.zip", ['Content-Type' => 'application/zip']);
        }

        $artifact = $this->service->loadExisting($project->id);
        if (!$artifact) {
            abort(404, '매뉴얼이 없습니다.');
        }

        if ($format === 'html') {
            $html = $this->service->toHtml($artifact->content ?? '', $project->name);

            return response()->streamDownload(
                fn() => print($html),
                "user-manual-{$slug}.html",
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        return response()->streamDownload(
            fn() => print($artifact->content ?? ''),
            "user-manual-{$slug}.md",
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $artifact = $this->service->loadExisting($project->id);

        if (!$artifact) {
            return response()->json(['error' => '매뉴얼이 없습니다.'], 404);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $artifact->update(['content' => $validated['content']]);

        return response()->json(['success' => true]);
    }
}
