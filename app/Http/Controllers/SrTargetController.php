<?php

namespace App\Http\Controllers;

use App\Models\SrTarget;
use Illuminate\Http\Request;

class SrTargetController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $srTarget = SrTarget::create([
            'title'            => $validated['title'],
            'project_id'       => $validated['project_id'] ?? null,
            'created_by'       => auth()->id(),
            'company_group_id' => auth()->user()->company_group_id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'id'       => $srTarget->id,
                'redirect' => route('sr-targets.maintenances.index', $srTarget),
            ]);
        }

        return redirect()->route('sr-targets.maintenances.index', $srTarget)
            ->with('success', 'SR 대상이 추가되었습니다.');
    }

    public function destroy(Request $request, SrTarget $srTarget)
    {
        $this->authorizeSrTarget($srTarget);

        $srTarget->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.index')
            ->with('success', 'SR 대상이 삭제되었습니다.');
    }

    private function authorizeSrTarget(SrTarget $srTarget): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }
        if ($srTarget->created_by !== $user->id) {
            abort(403, '접근 권한이 없습니다.');
        }
    }
}
