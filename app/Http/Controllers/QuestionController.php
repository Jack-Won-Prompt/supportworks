<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Question;
use App\Services\ProjectNotificationService;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Project $project)
    {
        $this->authorizeProject($project);

        $questions = $project->questions()
            ->with(['user', 'answers'])
            ->withCount('answers')
            ->latest()
            ->paginate(15);

        return view('questions.index', compact('project', 'questions'));
    }

    public function create(Project $project)
    {
        $this->authorizeProject($project);
        return view('questions.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_private' => 'boolean',
        ]);

        $question = $project->questions()->create([
            ...$validated,
            'user_id' => auth()->id(),
            'is_private' => $request->boolean('is_private'),
        ]);

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'question_created',
            $question->title,
            route('questions.show', $question),
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.questions.index', $project)
            ->with('success', '질문이 등록되었습니다.');
    }

    public function show(Question $question)
    {
        $this->authorizeProject($question->project);
        $question->load(['user', 'project', 'answers.user']);
        return view('questions.show', compact('question'));
    }

    public function edit(Question $question)
    {
        $this->authorizeProject($question->project);
        if ($question->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
        return view('questions.edit', compact('question'));
    }

    public function update(Request $request, Question $question)
    {
        $this->authorizeProject($question->project);
        if ($question->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_private' => 'boolean',
        ]);

        $question->update([
            ...$validated,
            'is_private' => $request->boolean('is_private'),
        ]);

        app(ProjectNotificationService::class)->notify(
            $question->project, auth()->user(), 'question_updated',
            $question->title,
            route('questions.show', $question),
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('questions.show', $question)
            ->with('success', '질문이 수정되었습니다.');
    }

    public function destroy(Question $question)
    {
        $project = $question->project;
        if ($question->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
        $title = $question->title;
        $question->delete();

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'question_deleted',
            $title,
            route('projects.questions.index', $project),
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.questions.index', $project)
            ->with('success', '질문이 삭제되었습니다.');
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }
}
