<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\TaskActions\TaskReopenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskReopenController extends Controller
{
    public function __construct(private TaskReopenService $service) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('reopen', $task);

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $new = $this->service->reopen($task, Auth::user(), $data['note'] ?? null);

        $next = $new->mode === 'new'
            ? route('wb.tasks.options.edit', $new)
            : route('wb.tasks.spec-review.show', $new);

        return redirect($next)->with('status', "Task #{$task->id}을(를) 재실행했습니다. 신규 Task #{$new->id}.");
    }
}
