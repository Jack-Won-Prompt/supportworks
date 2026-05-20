<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Jobs\WorksBuilder\RegenerateHtmlJob;
use App\Models\WorksBuilder\NgInput;
use App\Models\WorksBuilder\ReviewSession;
use App\Models\WorksBuilder\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NgInputController extends Controller
{
    public function create(Task $task, ReviewSession $session): View
    {
        $this->authorize('update', $task);
        abort_unless($session->task_id === $task->id, 404);

        $cmd        = session('wb_command_box', '');
        $highlights = json_decode((string) session('wb_highlights', '[]'), true) ?: [];

        return view('works-builder.ng-input.create', compact('task', 'session', 'cmd', 'highlights'));
    }

    public function store(Request $request, Task $task, ReviewSession $session): RedirectResponse
    {
        $this->authorize('update', $task);
        abort_unless($session->task_id === $task->id, 404);

        $data = $request->validate([
            'miss_description'    => 'required|string|max:5000',
            'command_box'         => 'nullable|string|max:5000',
            'highlights_snapshot' => 'nullable|string',
        ]);

        $highlights = json_decode((string) ($data['highlights_snapshot'] ?? '[]'), true) ?: [];

        $ng = NgInput::create([
            'task_id'              => $task->id,
            'review_session_id'    => $session->id,
            'review_round'         => $session->review_round,
            'highlights_snapshot'  => $highlights,
            'command_box'          => $data['command_box'] ?? null,
            'miss_description'     => $data['miss_description'],
            'reported_by'          => Auth::id(),
        ]);

        RegenerateHtmlJob::dispatch($task->id, $ng->id);

        return redirect()->route('wb.tasks.ai-progress.show', $task)
            ->with('status', 'NG 미스가 기록되었습니다. 웍스 재생성을 시작합니다.');
    }
}
