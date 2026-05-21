<?php

namespace App\Services\WorksBuilder\Ai\Logging;

use App\Models\WorksBuilder\AiCallLog;
use App\Models\WorksBuilder\InternalPrompt;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Ai\AiAttemptException;
use App\Services\WorksBuilder\Ai\AiResult;

/**
 * 명세 v11 §2.4 — AI 호출 로깅 헬퍼.
 */
class AiCallLogger
{
    public function startLog(
        Task $task,
        string $stage,
        ?int $reviewRound,
        InternalPrompt $prompt,
        string $primaryProvider = 'openai',
    ): AiCallLog {
        return AiCallLog::create([
            'task_id'             => $task->id,
            'stage'               => $stage,
            'review_round'        => $reviewRound,
            'internal_prompt_id'  => $prompt->id,
            'primary_provider'    => $primaryProvider,
            'fallback_used'       => false,
            'final_provider'      => 'none',
            'status'              => AiCallLog::STATUS_SUCCESS,
            'primary_attempt_status' => 'success',
        ]);
    }

    public function recordPrimaryFailure(AiCallLog $log, AiAttemptException $e): void
    {
        $log->update([
            'primary_attempt_status' => $e->statusCode,
            'primary_error_message'  => $e->getMessage(),
        ]);
    }

    public function recordFallbackUsed(AiCallLog $log): void
    {
        $log->update([
            'fallback_used'          => true,
            'fallback_attempt_status' => 'success',
        ]);
    }

    public function recordFallbackFailure(AiCallLog $log, AiAttemptException $e): void
    {
        $log->update([
            'fallback_used'           => true,
            'fallback_attempt_status' => $e->statusCode,
            'fallback_error_message'  => $e->getMessage(),
        ]);
    }

    public function finalize(AiCallLog $log, AiResult $result, string $status = AiCallLog::STATUS_SUCCESS): AiCallLog
    {
        $log->update([
            'status'             => $status,
            'final_provider'     => $result->provider,
            'prompt_tokens'      => $result->promptTokens,
            'completion_tokens'  => $result->completionTokens,
            'total_tokens'       => $result->totalTokens,
            'estimated_cost_usd' => $result->estimatedCostUsd,
            'response_time_ms'   => $result->responseTimeMs,
        ]);

        // 누적 통계
        $log->task->increment('total_ai_calls');
        $log->task->increment('total_tokens_used', $result->totalTokens ?? 0);
        $log->task->update([
            'total_cost_usd' => (float) $log->task->total_cost_usd + (float) ($result->estimatedCostUsd ?? 0),
        ]);

        return $log->refresh();
    }

    public function markFailed(AiCallLog $log): AiCallLog
    {
        $log->update([
            'status'         => AiCallLog::STATUS_FAILED,
            'final_provider' => 'none',
        ]);
        return $log->refresh();
    }

    public function markCancelled(AiCallLog $log): AiCallLog
    {
        $log->update([
            'status' => AiCallLog::STATUS_CANCELLED,
            'primary_attempt_status' => 'cancelled',
        ]);
        return $log->refresh();
    }
}
