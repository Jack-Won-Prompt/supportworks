<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFileCategory;
use Illuminate\Http\Request;

class ProjectFileCategoryController extends Controller
{
    public function index(Project $project)
    {
        $this->authorizeProject($project);
        return response()->json($project->fileCategories()->get(['id', 'name', 'color']));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $maxOrder = $project->fileCategories()->max('sort_order') ?? 0;

        $category = $project->fileCategories()->create([
            'name'       => $request->name,
            'color'      => $request->color ?? '#6366f1',
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['ok' => true, 'category' => $category->only('id', 'name', 'color')]);
    }

    public function update(Request $request, Project $project, ProjectFileCategory $category)
    {
        $this->authorizeProject($project);
        abort_unless($category->project_id === $project->id, 404);

        $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $category->update([
            'name'  => $request->name,
            'color' => $request->color,
        ]);

        return response()->json(['ok' => true, 'category' => $category->only('id', 'name', 'color')]);
    }

    public function destroy(Project $project, ProjectFileCategory $category)
    {
        $this->authorizeProject($project);
        abort_unless($category->project_id === $project->id, 404);

        $category->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }
}
