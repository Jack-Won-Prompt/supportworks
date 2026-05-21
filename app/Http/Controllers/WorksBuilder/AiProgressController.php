<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * AI 호출 진행 상태 화면 + polling 엔드포인트 + 취소.
 */
class AiProgressController extends Controller
{
    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $steps = TaskStep::where('task_id', $task->id)
            ->orderBy('sequence')
            ->get();

        return view('works-builder.ai-progress.show', compact('task', 'steps'));
    }

    public function status(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->refresh();
        $latestLog = $task->aiCallLogs()->latest('created_at')->first();

        $payload = [
            'task_status'   => $task->status,
            'current_stage' => $task->current_stage,
            'next_url'      => null,
            'log'           => null,
            'steps'         => TaskStep::where('task_id', $task->id)
                ->orderBy('sequence')
                ->get()
                ->map(fn ($s) => [
                    'sequence'    => $s->sequence,
                    'code'        => $s->code,
                    'label'       => $s->label,
                    'status'      => $s->status,
                    'context'     => $s->context,
                    'started_at'  => $s->started_at?->toIso8601String(),
                    'ended_at'    => $s->ended_at?->toIso8601String(),
                    'duration_ms' => $s->duration_ms,
                ])->toArray(),
        ];

        if ($latestLog) {
            $payload['log'] = [
                'id'                      => $latestLog->id,
                'stage'                   => $latestLog->stage,
                'status'                  => $latestLog->status,
                'fallback_used'           => (bool) $latestLog->fallback_used,
                'final_provider'          => $latestLog->final_provider,
                'primary_attempt_status'  => $latestLog->primary_attempt_status,
                'primary_error_message'   => $latestLog->primary_error_message,
                'fallback_attempt_status' => $latestLog->fallback_attempt_status,
                'fallback_error_message'  => $latestLog->fallback_error_message,
                'response_time_ms'        => $latestLog->response_time_ms,
                'total_tokens'            => $latestLog->total_tokens,
                'cost_usd'                => $latestLog->estimated_cost_usd,
            ];
        }

        // 다음 화면 결정
        if ($task->current_stage === 'result_confirm') {
            $payload['next_url'] = route('wb.tasks.result-confirm.show', $task);
        } elseif (in_array($task->current_stage, ['option_input', 'spec_review'], true)
                  && $task->status === Task::STATUS_IN_PROGRESS) {
            $payload['next_url'] = $task->current_stage === 'option_input'
                ? route('wb.tasks.options.edit', $task)
                : route('wb.tasks.spec-review.show', $task);
        }

        return response()->json($payload);
    }

    public function cancel(Task $task): RedirectResponse
    {
        $this->authorize('cancel', $task);

        // 큐의 보류 Job 삭제 (Laravel database 큐 — payload에 taskId 포함)
        DB::table('jobs')
            ->whereIn('queue', ['default'])
            ->where('payload', 'like', '%"taskId":'.$task->id.'%')
            ->delete();

        // 현재 호출 로그 cancelled로 마킹
        $latestLog = $task->aiCallLogs()->latest('created_at')->first();
        if ($latestLog && $latestLog->status === 'success' && $latestLog->total_tokens === null) {
            $latestLog->update(['status' => 'cancelled', 'primary_attempt_status' => 'cancelled']);
        }

        $task->update([
            'status'        => Task::STATUS_IN_PROGRESS,
            'current_stage' => $task->mode === 'enhance' ? 'spec_review' : 'option_input',
        ]);

        $next = $task->mode === 'enhance'
            ? route('wb.tasks.spec-review.show', $task)
            : route('wb.tasks.options.edit', $task);

        return redirect($next)->with('status', '웍스 호출이 취소되었습니다.');
    }
}
