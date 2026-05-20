<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Jobs\WorksBuilder\BuildOutputPackageJob;
use App\Models\WorksBuilder\HtmlIntegrityLog;
use App\Models\WorksBuilder\ReviewSession;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use App\Services\WorksBuilder\Review\HtmlIntegrityValidator;
use App\Services\WorksBuilder\Review\ReviewRoundManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 명세 v11 §1.6 — 검수 차수별 화면.
 */
class ReviewController extends Controller
{
    public function __construct(
        private ReviewRoundManager $rounds,
        private HtmlIntegrityValidator $integrity,
        private NotificationDispatcher $notifier,
    ) {}

    public function show(Task $task, ReviewSession $session): View
    {
        $this->authorize('view', $task);
        abort_unless($session->task_id === $task->id, 404);

        $session->load('html');
        $previous = ReviewSession::where('task_id', $task->id)
            ->where('review_round', $session->review_round - 1)
            ->with('html')
            ->first();

        return view('works-builder.review.show', compact('task', 'session', 'previous'));
    }

    public function decide(Request $request, Task $task, ReviewSession $session): RedirectResponse
    {
        $this->authorize('update', $task);
        abort_unless($session->task_id === $task->id, 404);

        $data = $request->validate([
            'decision'    => 'required|in:ok,ng',
            'command_box' => 'nullable|string',
            'highlights'  => 'nullable|string',
        ]);

        // 무결성 검증 + 로깅
        $session->load('html');
        $endHash = $this->integrity->hash($session->html->html_content);
        $passed  = hash_equals($session->start_hash, $endHash);

        HtmlIntegrityLog::create([
            'review_session_id' => $session->id,
            'generated_html_id' => $session->generated_html_id,
            'start_hash'        => $session->start_hash,
            'end_hash'          => $endHash,
            'passed'            => $passed,
            'failure_reason'    => $passed ? null : 'start_hash != end_hash',
            'checked_at'        => now(),
        ]);

        $this->rounds->endSession($session, $data['decision']);

        if ($data['decision'] === 'ok') {
            $task->update([
                'current_stage' => 'complete',
                'status'        => Task::STATUS_COMPLETED,
                'completed_at'  => now(),
            ]);

            BuildOutputPackageJob::dispatch($task->id);
            $this->notifier->dispatchStage($task, 'complete');

            return redirect()->route('wb.tasks.show', $task)
                ->with('status', '검수 OK — 작업이 완료되었습니다. 패키지가 백그라운드에서 빌드됩니다.');
        }

        // NG → ng-input
        $task->update(['current_stage' => 'ng_input']);
        $this->notifier->dispatchStage($task, 'ng_input', $session->review_round);

        return redirect()
            ->route('wb.tasks.ng-input.create', ['task' => $task, 'session' => $session->id])
            ->with('wb_command_box', $data['command_box'] ?? '')
            ->with('wb_highlights', $data['highlights'] ?? '[]');
    }
}
