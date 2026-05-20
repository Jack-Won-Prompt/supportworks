<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\TaskActions\TaskCancelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskCancelController extends Controller
{
    public function __construct(private TaskCancelService $service) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('cancel', $task);

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->service->cancel($task, Auth::user(), $data['reason'] ?? null);

        return redirect()->route('wb.tasks.index')
            ->with('status', "Task #{$task->id}이(가) 취소되었습니다.");
    }
}
