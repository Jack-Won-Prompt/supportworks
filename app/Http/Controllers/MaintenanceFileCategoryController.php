<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceFileCategory;
use App\Models\Project;
use Illuminate\Http\Request;

class MaintenanceFileCategoryController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorize($project);

        $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $maxOrder = $project->maintenanceFileCategories()->max('sort_order') ?? 0;

        $category = $project->maintenanceFileCategories()->create([
            'name'       => $request->name,
            'color'      => $request->color ?? '#7c3aed',
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['ok' => true, 'category' => $category->only('id', 'name', 'color')]);
    }

    public function destroy(Project $project, MaintenanceFileCategory $category)
    {
        $this->authorize($project);
        abort_unless($category->project_id === $project->id, 404);

        $category->delete();

        return response()->json(['ok' => true]);
    }

    private function authorize(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }
}
