<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Services\ProjectNotificationService;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $question->answers()->create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        if ($question->status === 'open') {
            $question->update(['status' => 'answered']);
        }

        $question->load('project');
        app(ProjectNotificationService::class)->notify(
            $question->project, auth()->user(), 'answer_created',
            $question->title,
            route('questions.show', $question),
        );

        return redirect()->route('questions.show', $question)
            ->with('success', '답변이 등록되었습니다.');
    }

    public function accept(Answer $answer)
    {
        $question = $answer->question;

        if ($question->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        // 기존 채택 해제
        $question->answers()->update(['is_accepted' => false]);
        $answer->update(['is_accepted' => true]);
        $question->update(['status' => 'answered']);

        return back()->with('success', '답변이 채택되었습니다.');
    }

    public function destroy(Answer $answer)
    {
        if ($answer->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
        $question = $answer->question->load('project');
        $answer->delete();

        if ($question->answers()->count() === 0) {
            $question->update(['status' => 'open']);
        }

        app(ProjectNotificationService::class)->notify(
            $question->project, auth()->user(), 'answer_deleted',
            $question->title,
            route('questions.show', $question),
        );

        return back()->with('success', '답변이 삭제되었습니다.');
    }
}
