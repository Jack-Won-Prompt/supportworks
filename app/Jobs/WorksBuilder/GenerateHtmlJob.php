<?php

namespace App\Jobs\WorksBuilder;

use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiCallOrchestrator;
use App\Services\WorksBuilder\Ai\PromptBuilders\HtmlGenerationPromptBuilder;
use App\Services\WorksBuilder\Ai\ResponseParsers\HtmlExtractor;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 명세 v11 §1.7 — 초기 HTML 생성 Job.
 */
class GenerateHtmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 1;

    public function __construct(public int $taskId) {}

    public function handle(
        HtmlGenerationPromptBuilder $promptBuilder,
        AiCallOrchestrator $orchestrator,
        HtmlExtractor $extractor,
        NotificationDispatcher $notifier,
    ): void {
        $task = Task::find($this->taskId);
        if (!$task) {
            Log::warning("[WB] GenerateHtmlJob: task not found {$this->taskId}");
            return;
        }
        if ($task->isImmutable()) {
            Log::warning("[WB] GenerateHtmlJob: task #{$task->id} is immutable, skip");
            return;
        }

        $task->update(['status' => Task::STATUS_AI_CALLING, 'current_stage' => 'ai_calling']);
        $notifier->dispatchStage($task, 'ai_calling');

        try {
            $internalPrompt = $promptBuilder->build($task);
            ['result' => $result, 'log' => $log] =
                $orchestrator->call($task, 'html_generation', null, $internalPrompt);

            $html = $extractor->extract($result->rawResponse);

            DB::transaction(function () use ($task, $result, $log, $html) {
                $row = GeneratedHtml::create([
                    'task_id'         => $task->id,
                    'version'         => 1,
                    'review_round'    => 0,
                    'generated_by'    => $result->provider,
                    'ai_call_log_id'  => $log->id,
                    'html_content'    => $html,
                    'html_hash'       => GeneratedHtml::hash($html),
                ]);
                $log->update(['generated_html_id' => $row->id]);

                $task->update([
                    'status'        => Task::STATUS_IN_PROGRESS,
                    'current_stage' => 'result_confirm',
                ]);
            });

            if ($log->fallback_used) {
                $notifier->dispatchStage($task, 'ai_fallback_used');
            }
            $notifier->dispatchStage($task, 'result_confirm');
        } catch (AiAttemptException $e) {
            Log::error('[WB] GenerateHtmlJob: AI 호출 실패', [
                'task_id' => $task->id,
                'status'  => $e->statusCode,
                'message' => $e->getMessage(),
            ]);
            $task->update([
                'status'        => Task::STATUS_IN_PROGRESS,
                'current_stage' => $task->mode === 'enhance' ? 'spec_review' : 'option_input',
            ]);
            $notifier->dispatchStage($task, 'ai_call_failed', null, null, null, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[WB] GenerateHtmlJob: 예외', [
                'task_id' => $task->id,
                'error'   => $e->getMessage(),
            ]);
            $task->update([
                'status'        => Task::STATUS_IN_PROGRESS,
                'current_stage' => $task->mode === 'enhance' ? 'spec_review' : 'option_input',
            ]);
            $notifier->dispatchStage($task, 'ai_call_failed', null, null, null, 'HTML 추출/저장 실패');
        }
    }
}
