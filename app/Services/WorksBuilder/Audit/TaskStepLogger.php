<?php

namespace App\Services\WorksBuilder\Audit;

use App\Models\WorksBuilder\AiCallLog;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskStep;

/**
 * Task 처리 과정 단계별 audit 로그 유틸리티.
 *
 * 사용 예:
 *   $logger->start($task, 'prompt_built', '프롬프트 빌드', ['system_tokens' => 1024]);
 *   $logger->finish($step, 'success', ['extra' => 1]);
 *
 *   // 또는 즉시 1-shot 기록:
 *   $logger->event($task, 'job_queued', 'Job 큐 등록');
 *
 *   // 자동 try/catch 래퍼:
 *   $result = $logger->measure($task, 'html_extract', 'HTML 추출', fn () => $extractor->extract(...));
 *
 * 모든 메서드는 자체 try/catch 로 audit 실패가 본 흐름을 막지 않도록 한다.
 */
class TaskStepLogger
{
    /** 시작 row 생성 — finish() 호출 시 status/duration 갱신. */
    public function start(Task $task, string $code, string $label, array $context = [], ?int $aiCallLogId = null): ?TaskStep
    {
        try {
            $sequence = (int) TaskStep::where('task_id', $task->id)->max('sequence') + 1;
            return TaskStep::create([
                'task_id'        => $task->id,
                'ai_call_log_id' => $aiCallLogId,
                'sequence'       => $sequence,
                'code'           => $code,
                'label'          => $label,
                'status'         => TaskStep::STATUS_RUNNING,
                'context'        => $context,
                'started_at'     => now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /** running step 을 success/failed 로 종료. */
    public function finish(?TaskStep $step, string $status = TaskStep::STATUS_SUCCESS, array $context = []): void
    {
        if (!$step) return;
        try {
            $endedAt   = now();
            $startedAt = $step->started_at ?? $endedAt;
            $durationMs = max(0, (int) (($endedAt->getTimestamp() - $startedAt->getTimestamp()) * 1000)
                + (int) (($endedAt->microsecond - $startedAt->microsecond) / 1000));

            $merged = array_merge($step->context ?? [], $context);

            $step->update([
                'status'      => $status,
                'context'     => $merged,
                'ended_at'    => $endedAt,
                'duration_ms' => $durationMs,
            ]);
        } catch (\Throwable) {
            // ignore
        }
    }

    /** 1-shot 이벤트 (즉시 완료된 상태로 기록). */
    public function event(Task $task, string $code, string $label, string $status = TaskStep::STATUS_SUCCESS, array $context = []): ?TaskStep
    {
        try {
            $sequence = (int) TaskStep::where('task_id', $task->id)->max('sequence') + 1;
            $now      = now();
            return TaskStep::create([
                'task_id'    => $task->id,
                'sequence'   => $sequence,
                'code'       => $code,
                'label'      => $label,
                'status'     => $status,
                'context'    => $context,
                'started_at' => $now,
                'ended_at'   => $now,
                'duration_ms'=> 0,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 클로저 실행을 감싸 자동으로 start/finish.
     * 클로저가 throw 하면 status=failed 로 기록 후 re-throw.
     */
    public function measure(Task $task, string $code, string $label, callable $fn, array $startContext = [])
    {
        $step = $this->start($task, $code, $label, $startContext);
        try {
            $result = $fn($step);
            $this->finish($step, TaskStep::STATUS_SUCCESS);
            return $result;
        } catch (\Throwable $e) {
            $this->finish($step, TaskStep::STATUS_FAILED, [
                'error_class'   => get_class($e),
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);
            throw $e;
        }
    }

    /** AI 호출 로그 연결 (ai_call_log_id 갱신). */
    public function attachAiCallLog(?TaskStep $step, ?AiCallLog $log): void
    {
        if (!$step || !$log) return;
        try {
            $step->update(['ai_call_log_id' => $log->id]);
        } catch (\Throwable) {}
    }
}
