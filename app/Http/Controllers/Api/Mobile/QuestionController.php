<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Project;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $questions = Question::where('project_id', $project->id)
            ->with(['user', 'answers.user'])
            ->withCount('answers')
            ->latest()
            ->get();

        return response()->json($questions->map(fn($q) => $this->questionResource($q)));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $question = Question::create([
            'project_id' => $project->id,
            'user_id'    => $request->user()->id,
            'title'      => $request->title,
            'content'    => $request->content,
            'status'     => 'open',
        ]);

        $question->load('user');

        return response()->json($this->questionResource($question), 201);
    }

    public function show(Request $request, Question $question): JsonResponse
    {
        $question->load(['user', 'answers.user', 'project']);

        return response()->json([
            ...$this->questionResource($question),
            'answers' => $question->answers->map(fn($a) => [
                'id'          => $a->id,
                'content'     => $a->content,
                'is_accepted' => $a->is_accepted,
                'user'        => ['id' => $a->user->id, 'name' => $a->user->name],
                'created_at'  => $a->created_at,
            ]),
        ]);
    }

    public function destroy(Request $request, Question $question): JsonResponse
    {
        abort_if($question->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
        $question->delete();
        return response()->json(['message' => '질문이 삭제되었습니다.']);
    }

    public function storeAnswer(Request $request, Question $question): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $answer = Answer::create([
            'question_id' => $question->id,
            'user_id'     => $request->user()->id,
            'content'     => $request->content,
        ]);

        $answer->load('user');

        return response()->json([
            'id'          => $answer->id,
            'content'     => $answer->content,
            'is_accepted' => $answer->is_accepted,
            'user'        => ['id' => $answer->user->id, 'name' => $answer->user->name],
            'created_at'  => $answer->created_at,
        ], 201);
    }

    public function acceptAnswer(Request $request, Answer $answer): JsonResponse
    {
        $question = $answer->question;
        abort_if($question->user_id !== $request->user()->id, 403);

        Answer::where('question_id', $question->id)->update(['is_accepted' => false]);
        $answer->update(['is_accepted' => true]);
        $question->update(['status' => 'resolved']);

        return response()->json(['message' => '답변이 채택되었습니다.']);
    }

    public function destroyAnswer(Request $request, Answer $answer): JsonResponse
    {
        abort_if($answer->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
        $answer->delete();
        return response()->json(['message' => '답변이 삭제되었습니다.']);
    }

    private function questionResource(Question $q): array
    {
        return [
            'id'             => $q->id,
            'title'          => $q->title,
            'content'        => $q->content,
            'status'         => $q->status,
            'answers_count'  => $q->answers_count ?? ($q->answers ? $q->answers->count() : 0),
            'user'           => $q->user ? ['id' => $q->user->id, 'name' => $q->user->name] : null,
            'created_at'     => $q->created_at,
        ];
    }

    private function authorizeProject($user, Project $project): void
    {
        if ($user->isAdmin()) return;
        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');
    }
}