<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PromptBuilder\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SequenceController extends Controller
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

        $sequences = Sequence::where('project_id', $project->id)
            ->with(['workspace', 'owner'])
            ->withCount('builders')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('prompt-builder.sequences.index', compact('project', 'sequences'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'workspace_id' => 'required|exists:pb_workspaces,id',
            'ai_type'      => 'required|in:cursor,claude,openai',
        ]);

        $sequence = Sequence::create([
            'project_id'   => $project->id,
            'owner_id'     => Auth::id(),
            ...$validated,
        ]);

        return response()->json(['sequence' => $sequence]);
    }

    public function show(Sequence $sequence)
    {
        $this->authorizeProject($sequence->project);

        $sequence->load(['workspace', 'owner', 'builders' => fn($q) => $q->orderBy('sequence_step_number')]);

        return view('prompt-builder.sequences.show', compact('sequence'));
    }

    public function updateProgress(Request $request, Sequence $sequence)
    {
        $this->authorizeProject($sequence->project);

        $validated = $request->validate([
            'current_step'    => 'required|integer|min:0',
            'completed_steps' => 'array',
            'status'          => 'in:active,paused,completed,archived',
        ]);

        $sequence->update($validated);

        return response()->json(['sequence' => $sequence->fresh()]);
    }

    public function export(Sequence $sequence)
    {
        $this->authorizeProject($sequence->project);

        $sequence->load(['builders' => fn($q) => $q->orderBy('sequence_step_number')]);

        $export = [
            'sequence' => [
                'name'    => $sequence->name,
                'ai_type' => $sequence->ai_type,
                'steps'   => $sequence->builders->map(fn($b) => [
                    'step'    => $b->sequence_step_number,
                    'title'   => $b->title,
                    'content' => $b->content,
                ]),
            ],
        ];

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="sequence-' . $sequence->id . '.json"');
    }
}
