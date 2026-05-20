<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\TaskActions\TaskCloneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TaskCloneController extends Controller
{
    public function __construct(private TaskCloneService $service) {}

    public function store(Task $task): RedirectResponse
    {
        $this->authorize('clone', $task);

        $new = $this->service->clone($task, Auth::user());

        $next = $new->mode === 'new'
            ? route('wb.tasks.options.edit', $new)
            : route('wb.tasks.spec-review.show', $new);

        return redirect($next)->with('status', "Task #{$task->id}을(를) 복제했습니다. 신규 Task #{$new->id}.");
    }
}
