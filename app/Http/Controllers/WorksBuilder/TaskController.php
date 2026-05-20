<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use App\Services\WorksBuilder\TaskManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private TaskManager $tasks,
        private NotificationDispatcher $notifier,
    ) {}

    public function index(): View
    {
        $inProgress = Task::with('project', 'assignee')
            ->where('assignee_id', Auth::id())
            ->whereIn('status', [Task::STATUS_DRAFT, Task::STATUS_IN_PROGRESS, Task::STATUS_AI_CALLING, Task::STATUS_REVIEW])
            ->latest()
            ->paginate(20);

        return view('works-builder.tasks.index', compact('inProgress'));
    }

    public function create(): View
    {
        $projects = Project::query()
            ->where('created_by', Auth::id())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['planningDocs' => fn ($q) => $q->orderByDesc('updated_at')])
            ->orderBy('name')
            ->get();

        $planningDocsByProject = $projects->mapWithKeys(fn ($p) => [
            $p->id => $p->planningDocs->map(fn ($d) => [
                'id'           => $d->id,
                'title'        => $d->title,
                'version'      => (int) $d->version,
                'status_label' => $d->status_label,
            ])->values(),
        ]);

        // 재실행/복제 후보 — 사용자가 접근 가능한 완료 Task 최근 50건
        $completedTasks = Task::with('project')
            ->where('assignee_id', Auth::id())
            ->where('status', Task::STATUS_COMPLETED)
            ->latest('completed_at')
            ->limit(50)
            ->get();

        return view('works-builder.tasks.create', compact('projects', 'planningDocsByProject', 'completedTasks'));
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id'      => 'required|exists:projects,id',
            'mode'            => 'required|in:new,enhance',
            'planning_doc_id' => 'nullable|integer|exists:planning_docs,id',
        ]);

        if ($data['mode'] === 'enhance' && !empty($data['planning_doc_id'])) {
            $data['spec_reference_type'] = 'planning_doc';
            $data['spec_reference_id']   = (int) $data['planning_doc_id'];
        }
        unset($data['planning_doc_id']);
        $data['assignee_id'] = Auth::id();

        $task = $this->tasks->start($data);

        $this->notifier->dispatchStage($task, 'started');
        $this->notifier->dispatchStage($task, $task->mode === 'new' ? 'option_input' : 'spec_review');

        $next = $task->mode === 'new'
            ? route('wb.tasks.options.edit', $task)
            : route('wb.tasks.spec-review.show', $task);

        return redirect($next);
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load([
            'project', 'assignee', 'currentOption', 'options',
            'generatedHtml.aiCallLog', 'reviewSessions',
            'outputPackages', 'aiCallLogs',
            'parent', 'children',
        ]);

        $latestPackage = $task->outputPackages->sortByDesc('built_at')->first();

        return view('works-builder.tasks.show', compact('task', 'latestPackage'));
    }

    public function completed(): View
    {
        $completed = Task::with('project')
            ->where('assignee_id', Auth::id())
            ->where('status', Task::STATUS_COMPLETED)
            ->latest('completed_at')
            ->paginate(20);

        return view('works-builder.tasks.completed', compact('completed'));
    }
}
