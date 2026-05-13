<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PromptBuilder\Builder;
use App\Models\PromptBuilder\ExternalFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
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

        $feedbacks = ExternalFeedback::whereHas('builder', fn($q) => $q->where('project_id', $project->id))
            ->with(['builder', 'uploader'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('prompt-builder.feedback.index', compact('project', 'feedbacks'));
    }

    public function uploadForm(Project $project)
    {
        $this->authorizeProject($project);

        $builders = Builder::where('project_id', $project->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'ai_type', 'current_version']);

        return view('prompt-builder.feedback.upload', compact('project', 'builders'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'builder_id'    => 'required|exists:pb_builders,id',
            'upload_method' => 'required|in:file,zip,text',
            'user_rating'   => 'nullable|integer|min:1|max:5',
            'user_memo'     => 'nullable|string|max:2000',
            'archive'       => 'nullable|file|max:51200',
            'files.*'       => 'nullable|file',
        ]);

        $builder = Builder::findOrFail($validated['builder_id']);

        $archivePath  = null;
        $uploadedFiles = [];

        if ($request->hasFile('archive')) {
            $archivePath = $request->file('archive')->store('prompt-builder/feedback', 'private');
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $file->store('prompt-builder/feedback/files', 'private'),
                    'size' => $file->getSize(),
                ];
            }
        }

        $feedback = ExternalFeedback::create([
            'builder_id'     => $builder->id,
            'builder_version' => $builder->current_version,
            'uploaded_by'    => Auth::id(),
            'upload_method'  => $validated['upload_method'],
            'archive_path'   => $archivePath,
            'uploaded_files' => $uploadedFiles,
            'user_rating'    => $validated['user_rating'] ?? null,
            'user_memo'      => $validated['user_memo'] ?? null,
            'status'         => 'uploaded',
        ]);

        return redirect()->route('builder.feedback.show', $feedback)
            ->with('success', '피드백이 업로드되었습니다.');
    }

    public function show(ExternalFeedback $feedback)
    {
        $this->authorizeProject($feedback->builder->project);

        $feedback->load(['builder', 'uploader']);

        return view('prompt-builder.feedback.show', compact('feedback'));
    }

    public function applyImprovements(Request $request, ExternalFeedback $feedback)
    {
        $this->authorizeProject($feedback->builder->project);

        $validated = $request->validate([
            'improvements' => 'required|array',
        ]);

        $feedback->update([
            'applied_improvements' => $validated['improvements'],
            'status'               => 'applied',
        ]);

        return response()->json(['feedback' => $feedback->fresh()]);
    }
}
