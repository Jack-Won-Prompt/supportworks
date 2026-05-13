<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PromptBuilder\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }

    public function index(Project $project)
    {
        $this->authorizeProject($project);

        $templates = Template::where(fn($q) =>
                $q->where('owner_id', Auth::id())
                  ->orWhere('share_scope', 'public')
                  ->orWhere(fn($q2) => $q2->where('share_scope', 'team')->where('project_id', $project->id))
            )
            ->with('owner')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('prompt-builder.templates.index', compact('project', 'templates'));
    }

    public function all()
    {
        $templates = Template::where(fn($q) =>
                $q->where('owner_id', Auth::id())
                  ->orWhere('share_scope', 'public')
            )
            ->with('owner')
            ->orderByDesc('usage_count')
            ->paginate(30);

        return view('prompt-builder.templates.all', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'tags'             => 'array',
            'share_scope'      => 'in:private,team,public',
            'project_id'       => 'nullable|exists:projects,id',
            'context_template' => 'nullable|array',
            'purpose_template' => 'nullable|array',
            'builder_structure' => 'nullable|array',
        ]);

        $template = Template::create([
            'owner_id' => Auth::id(),
            ...$validated,
        ]);

        return response()->json(['template' => $template]);
    }

    public function destroy(Template $template)
    {
        abort_if($template->owner_id !== Auth::id(), 403);

        $template->delete();

        return response()->json(['message' => 'Template deleted']);
    }
}
