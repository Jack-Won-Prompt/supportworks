<?php

namespace App\Jobs\WorksBuilder;

use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiCallOrchestrator;
use App\Services\WorksBuilder\Ai\PromptBuilders\HtmlGenerationPromptBuilder;
use App\Services\WorksBuilder\Ai\ResponseParsers\HtmlExtractor;
use App\Services\WorksBuilder\Audit\TaskStepLogger;
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
 *
 * 각 단계마다 TaskStepLogger 로 처리 과정을 기록 (사용자가 진행 화면에서 확인 가능).
 */
class GenerateHtmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // Claude 120s + OpenAI 180s 최악 폴백 시나리오 고려
    public int $tries   = 1;

    public function __construct(public int $taskId) {}

    public function handle(
        HtmlGenerationPromptBuilder $promptBuilder,
        AiCallOrchestrator $orchestrator,
        HtmlExtractor $extractor,
        NotificationDispatcher $notifier,
        TaskStepLogger $audit,
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

        $audit->event($task, 'job_started', 'GenerateHtmlJob 시작', context: ['job_class' => static::class]);

        $task->update(['status' => Task::STATUS_AI_CALLING, 'current_stage' => 'ai_calling']);
        $notifier->dispatchStage($task, 'ai_calling');

        try {
            // 1) 프롬프트 빌드
            $internalPrompt = $audit->measure($task, 'prompt_built', '프롬프트 빌드',
                fn () => $promptBuilder->build($task),
            );
            $audit->event($task, 'prompt_metrics', '프롬프트 토큰 추정', context: [
                'system_chars' => mb_strlen($internalPrompt->system_prompt ?? ''),
                'user_chars'   => mb_strlen($internalPrompt->user_prompt ?? ''),
            ]);

            // 2) AI 호출 (OpenAI primary → Claude fallback)
            $aiStep = $audit->start($task, 'ai_call', 'AI 호출 (OpenAI → Claude 폴백)');
            try {
                ['result' => $result, 'log' => $log] =
                    $orchestrator->call($task, 'html_generation', null, $internalPrompt);
                $audit->attachAiCallLog($aiStep, $log);
                $audit->finish($aiStep, 'success', [
                    'final_provider'  => $result->provider,
                    'fallback_used'   => (bool) $log->fallback_used,
                    'prompt_tokens'   => $result->promptTokens,
                    'completion_tokens'=> $result->completionTokens,
                    'response_time_ms'=> $result->responseTimeMs,
                    'cost_usd'        => (float) $result->estimatedCostUsd,
                ]);
            } catch (\Throwable $e) {
                $audit->finish($aiStep, 'failed', [
                    'error_message' => mb_substr($e->getMessage(), 0, 500),
                ]);
                throw $e;
            }

            // 3) HTML 추출
            $html = $audit->measure($task, 'html_extracted', 'HTML 추출',
                fn () => $extractor->extract($result->rawResponse),
                startContext: ['raw_chars' => mb_strlen($result->rawResponse)],
            );

            // 4) DB 저장 + stage 전환
            $saveStep = $audit->start($task, 'html_saved', 'GeneratedHtml 저장 + stage 전환');
            try {
                DB::transaction(function () use ($task, $result, $log, $html, &$row) {
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
                $audit->finish($saveStep, 'success', [
                    'html_id'    => $row->id ?? null,
                    'html_bytes' => mb_strlen($html),
                ]);
            } catch (\Throwable $e) {
                $audit->finish($saveStep, 'failed', ['error_message' => $e->getMessage()]);
                throw $e;
            }

            // 5) 알림
            if ($log->fallback_used) {
                $audit->event($task, 'notify_fallback', '폴백 사용 알림', context: ['stage_code' => 'ai_fallback_used']);
                $notifier->dispatchStage($task, 'ai_fallback_used');
            }
            $audit->event($task, 'notify_done', '결과 확인 단계 알림 발송', context: ['stage_code' => 'result_confirm']);
            $notifier->dispatchStage($task, 'result_confirm');

            $audit->event($task, 'job_completed', 'GenerateHtmlJob 완료');
        } catch (AiAttemptException $e) {
            Log::error('[WB] GenerateHtmlJob: AI 호출 실패', [
                'task_id' => $task->id,
                'status'  => $e->statusCode,
                'message' => $e->getMessage(),
            ]);
            $audit->event($task, 'job_failed', 'AI 호출 실패로 Job 종료', 'failed', [
                'status_code' => $e->statusCode,
                'message'     => mb_substr($e->getMessage(), 0, 500),
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
            $audit->event($task, 'job_failed', '예외 발생으로 Job 종료', 'failed', [
                'error_class' => get_class($e),
                'message'     => mb_substr($e->getMessage(), 0, 500),
            ]);
            $task->update([
                'status'        => Task::STATUS_IN_PROGRESS,
                'current_stage' => $task->mode === 'enhance' ? 'spec_review' : 'option_input',
            ]);
            $notifier->dispatchStage($task, 'ai_call_failed', null, null, null, 'HTML 추출/저장 실패');
        }
    }
}
