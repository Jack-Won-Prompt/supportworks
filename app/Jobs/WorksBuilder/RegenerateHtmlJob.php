<?php

namespace App\Jobs\WorksBuilder;

use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\NgInput;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiCallOrchestrator;
use App\Services\WorksBuilder\Ai\PromptBuilders\RegenerationPromptBuilder;
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
 * 명세 v11 §1.8 — NG 후 재생성 Job.
 */
class RegenerateHtmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 1;

    public function __construct(public int $taskId, public int $ngInputId) {}

    public function handle(
        RegenerationPromptBuilder $promptBuilder,
        AiCallOrchestrator $orchestrator,
        HtmlExtractor $extractor,
        NotificationDispatcher $notifier,
    ): void {
        $task = Task::find($this->taskId);
        $ng   = NgInput::find($this->ngInputId);

        if (!$task || !$ng || $ng->task_id !== $task->id) {
            Log::warning('[WB] RegenerateHtmlJob: task or NgInput not found');
            return;
        }
        if ($task->isImmutable()) return;

        $nextRound = $ng->review_round + 1;

        $task->update(['status' => Task::STATUS_AI_CALLING, 'current_stage' => 'ai_calling']);
        $notifier->dispatchStage($task, 'ai_calling', $nextRound);

        try {
            $internalPrompt = $promptBuilder->buildWithNg($task, $ng, $nextRound);
            ['result' => $result, 'log' => $log] =
                $orchestrator->call($task, 'regeneration', $nextRound, $internalPrompt);

            $html = $extractor->extract($result->rawResponse);

            DB::transaction(function () use ($task, $result, $log, $html, $nextRound, $ng) {
                $latestVersion = GeneratedHtml::where('task_id', $task->id)->max('version') ?? 0;

                $row = GeneratedHtml::create([
                    'task_id'         => $task->id,
                    'version'         => $latestVersion + 1,
                    'review_round'    => $nextRound,
                    'generated_by'    => $result->provider,
                    'ai_call_log_id'  => $log->id,
                    'html_content'    => $html,
                    'html_hash'       => GeneratedHtml::hash($html),
                ]);
                $log->update(['generated_html_id' => $row->id]);

                $ng->update(['processed_for_learning' => false, 'processed_at' => now()]);

                $task->update([
                    'status'        => Task::STATUS_IN_PROGRESS,
                    'current_stage' => 'result_confirm',
                    'current_review_round' => $nextRound,
                ]);
            });

            if ($log->fallback_used) {
                $notifier->dispatchStage($task, 'ai_fallback_used');
            }
            $notifier->dispatchStage($task, 'result_confirm', $nextRound);
        } catch (AiAttemptException $e) {
            Log::error('[WB] RegenerateHtmlJob: AI 호출 실패', [
                'task_id' => $task->id,
                'status'  => $e->statusCode,
                'message' => $e->getMessage(),
            ]);
            $task->update([
                'status'        => Task::STATUS_IN_PROGRESS,
                'current_stage' => 'ng_input',
            ]);
            $notifier->dispatchStage($task, 'ai_call_failed', null, null, null, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[WB] RegenerateHtmlJob: 예외', ['task_id' => $task->id, 'error' => $e->getMessage()]);
            $task->update([
                'status'        => Task::STATUS_IN_PROGRESS,
                'current_stage' => 'ng_input',
            ]);
            $notifier->dispatchStage($task, 'ai_call_failed', null, null, null, 'HTML 추출/저장 실패');
        }
    }
}
