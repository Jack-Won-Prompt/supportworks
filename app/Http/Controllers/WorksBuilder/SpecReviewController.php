<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Jobs\WorksBuilder\GenerateHtmlJob;
use App\Models\PlanningDoc;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\TaskActions\OptionRevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 명세 v11 §1.3 모드 B — 기획서 검토 후 옵션 확정 → AI HTML 생성.
 */
class SpecReviewController extends Controller
{
    public function __construct(private OptionRevisionService $revision) {}

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load('currentOption', 'project');

        $plan = null;
        if ($task->spec_reference_type === 'planning_doc' && $task->spec_reference_id) {
            $plan = PlanningDoc::find($task->spec_reference_id);
        }

        return view('works-builder.spec-review.show', compact('task', 'plan'));
    }

    public function confirm(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'gnb_position'    => 'required|in:top,left,right',
            'tab_structure'   => 'required|in:single,top_tabs,left_tabs,sidebar_tabs,none',
            'transition_type' => 'required|in:page,slide,tab_switch',
            'main_color'      => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->revision->revise($task, $data, Auth::user());

        // 폴링이 옛 상태를 보고 즉시 리다이렉트하는 race를 막기 위해 동기 전환
        $task->update([
            'status'        => Task::STATUS_AI_CALLING,
            'current_stage' => 'ai_calling',
        ]);

        GenerateHtmlJob::dispatch($task->id);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'         => true,
                'status_url' => route('wb.tasks.ai-progress.status', $task),
                'cancel_url' => route('wb.tasks.ai-progress.cancel', $task),
            ]);
        }

        return redirect()->route('wb.tasks.ai-progress.show', $task);
    }
}
