<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PromptBuilder\StandardAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuilderController extends Controller
{
    public function index()
    {
        return redirect()->route('builder.new');
    }

    public function getProjects()
    {
        $user = Auth::user();

        $projects = Project::where('created_by', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->with(['pbWorkspaces'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($project) => [
                'id'         => $project->id,
                'name'       => $project->name,
                'tech_stack' => [
                    'framework'         => $project->pb_framework,
                    'framework_version' => $project->pb_framework_version,
                    'language'          => $project->pb_language,
                    'styling'           => $project->pb_styling,
                ],
                'workspaces_count' => $project->pbWorkspaces->count(),
                'last_activity'    => $project->updated_at,
            ]);

        return response()->json($projects);
    }

    public function getWorkspaces(Project $project)
    {
        abort_unless($this->canAccessProject($project), 403);
        return response()->json($project->pbWorkspaces()->get());
    }

    public function createWorkspace(Project $project, Request $request)
    {
        abort_unless($this->canAccessProject($project), 403);

        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'framework' => 'nullable|string|max:50',
            'language'  => 'nullable|string|max:50',
            'styling'   => 'nullable|string|max:50',
        ]);

        $workspace = $project->pbWorkspaces()->create($validated);

        return response()->json($workspace, 201);
    }

    private function canAccessProject(Project $project): bool
    {
        $userId = Auth::id();
        return $project->created_by === $userId
            || $project->members()->where('user_id', $userId)->exists();
    }

    public function getStandards(Project $project)
    {
        abort_unless($this->canAccessProject($project), 403);
        $workspaceId = request('workspace_id');

        $assets = StandardAsset::whereHas('workspace', fn($q) => $q->where('project_id', $project->id))
            ->when($workspaceId, fn($q) => $q->where('workspace_id', $workspaceId))
            ->where('is_active', true)
            ->get();

        return response()->json([
            'layouts'       => $assets->where('asset_type', 'layout')->values(),
            'components'    => $assets->where('asset_type', 'component')->values(),
            'css_tokens'    => $assets->where('asset_type', 'css_token')->values(),
            'js_utilities'  => $assets->where('asset_type', 'js_utility')->values(),
        ]);
    }
}
