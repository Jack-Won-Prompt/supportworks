<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Controller as BaseController;
use App\Jobs\WorksBuilder\GenerateHtmlJob;
use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use App\Services\WorksBuilder\Review\ResultConfirmationService;
use App\Services\WorksBuilder\Review\ReviewRoundManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AI 생성 결과 1차 확인 (재생성 / 검수 진행).
 */
class ResultConfirmController extends Controller
{
    public function __construct(
        private ResultConfirmationService $service,
        private NotificationDispatcher $notifier,
        private ReviewRoundManager $rounds,
    ) {}

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load('generatedHtml');

        $latest = $task->generatedHtml()->orderByDesc('version')->first();
        return view('works-builder.result-confirm.show', compact('task', 'latest'));
    }

    public function decide(Request $request, Task $task, GeneratedHtml $html): RedirectResponse
    {
        $this->authorize('update', $task);
        abort_unless($html->task_id === $task->id, 404);

        $data = $request->validate([
            'decision' => 'required|in:regenerate,proceed_to_review',
            'note'     => 'nullable|string|max:2000',
        ]);

        $this->service->decide($task, $html, $data['decision'], $data['note'] ?? null, Auth::user());

        if ($data['decision'] === 'regenerate') {
            // 동일 옵션·기획서로 다시 호출 (NG 미스 입력 없이)
            GenerateHtmlJob::dispatch($task->id);
            return redirect()->route('wb.tasks.ai-progress.show', $task);
        }

        // 검수 진행 — 세션 생성
        $session = $this->rounds->startSession($task, $html, Auth::user());
        $this->notifier->dispatchStage($task, 'qa_review', $session->review_round);

        return redirect()->route('wb.tasks.review.show', ['task' => $task, 'session' => $session->id]);
    }
}
