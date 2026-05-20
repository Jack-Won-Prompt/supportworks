<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WorksBuilder\ChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChecklistController extends Controller
{
    /** 사이드바에서 인자 없이 진입 시 첫 접근 가능 프로젝트로 이동 */
    public function entry(): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $project = Project::query()
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('projectMembers', fn ($m) => $m->where('user_id', $user->id));
            })
            ->orderBy('name')
            ->first();

        if (!$project) {
            return redirect()->route('wb.tasks.index')
                ->with('status', '접근 가능한 프로젝트가 없습니다.');
        }
        return redirect()->route('wb.checklists.index', $project);
    }

    public function index(Request $request, Project $project): View
    {
        abort_unless($this->canAccess($project), 403);
        $category = $request->query('category');

        $query = ChecklistItem::forProject($project->id)->orderBy('category')->orderBy('id');
        if ($category) $query->where('category', $category);
        $items = $query->get();

        $counts = ChecklistItem::forProject($project->id)
            ->selectRaw('category, SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as active_cnt, COUNT(*) as total_cnt')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        return view('works-builder.checklists.index', compact('project', 'items', 'counts', 'category'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($this->canAccess($project), 403);

        $data = $request->validate([
            'category'          => 'required|in:html_structure,semantic,class_naming,design_tokens,typography,accessibility',
            'title'             => 'required|string|max:200',
            'description'       => 'nullable|string|max:1000',
            'check_prompt_text' => 'required|string|max:2000',
        ]);

        ChecklistItem::create($data + [
            'project_id' => $project->id,
            'is_active'  => true,
            'added_at'   => now(),
        ]);

        return back()->with('status', '체크 항목이 추가되었습니다.');
    }

    public function toggle(Project $project, ChecklistItem $item): RedirectResponse
    {
        abort_unless($this->canAccess($project), 403);
        abort_unless($item->project_id === $project->id, 404);

        $item->update(['is_active' => !$item->is_active]);
        return back();
    }

    private function canAccess(Project $project): bool
    {
        $user = Auth::user();
        if ($user->isAdmin()) return true;
        if ($project->created_by === $user->id) return true;
        return $project->projectMembers()->where('user_id', $user->id)->exists();
    }
}
