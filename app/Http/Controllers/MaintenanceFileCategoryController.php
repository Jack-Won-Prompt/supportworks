<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceFileCategory;
use App\Models\SrTarget;
use Illuminate\Http\Request;

class MaintenanceFileCategoryController extends Controller
{
    public function store(Request $request, SrTarget $srTarget)
    {
        $this->authorizeSrTarget($srTarget);

        $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $maxOrder = $srTarget->maintenanceFileCategories()->max('sort_order') ?? 0;

        $category = $srTarget->maintenanceFileCategories()->create([
            'name'       => $request->name,
            'color'      => $request->color ?? '#7c3aed',
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['ok' => true, 'category' => $category->only('id', 'name', 'color')]);
    }

    public function destroy(SrTarget $srTarget, MaintenanceFileCategory $category)
    {
        $this->authorizeSrTarget($srTarget);
        abort_unless($category->sr_target_id === $srTarget->id, 404);

        $category->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeSrTarget(SrTarget $srTarget): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$srTarget->isAccessibleBy($user)) abort(403);
    }
}
