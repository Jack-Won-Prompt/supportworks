<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Agent\DeployGuideDataContext;
use App\Services\Agent\DeployGuideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeployGuideController extends Controller
{
    public function __construct(
        private readonly DeployGuideService     $service,
        private readonly DeployGuideDataContext $dataContext,
    ) {}

    public function index(Project $project): View
    {
        $artifact = $this->service->loadExisting($project->id);
        $context  = $this->dataContext->build($project->id);

        return view('ai-agent.release.deploy-guide.index', [
            'artifact' => $artifact,
            'context'  => $context,
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
            return response()->json(['error' => '배포 가이드가 없습니다.'], 404);
        }

        return response()->json([
            'content'      => $artifact->content,
            'generated_at' => $artifact->created_at->format('Y-m-d H:i'),
        ]);
    }

    public function export(Request $request, Project $project)
    {
        $artifact = $this->service->loadExisting($project->id);

        if (!$artifact) {
            abort(404, '배포 가이드가 없습니다.');
        }

        $format = $request->query('format', 'md');

        if ($format === 'html') {
            $html    = $this->service->toHtml($artifact->content ?? '');
            $slug    = \Illuminate\Support\Str::slug($project->name) ?: 'project';
            $headers = ['Content-Type' => 'text/html; charset=UTF-8'];

            return response()->streamDownload(
                fn() => print($html),
                "deploy-guide-{$slug}.html",
                $headers
            );
        }

        $slug = \Illuminate\Support\Str::slug($project->name) ?: 'project';

        return response()->streamDownload(
            fn() => print($artifact->content ?? ''),
            "deploy-guide-{$slug}.md",
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $artifact = $this->service->loadExisting($project->id);

        if (!$artifact) {
            return response()->json(['error' => '배포 가이드가 없습니다.'], 404);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $artifact->update(['content' => $validated['content']]);

        return response()->json(['success' => true]);
    }
}
