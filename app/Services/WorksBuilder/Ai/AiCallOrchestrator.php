<?php

namespace App\Services\WorksBuilder\Ai;

use App\Models\WorksBuilder\AiCallLog;
use App\Models\WorksBuilder\InternalPrompt;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Ai\Logging\AiCallLogger;
use App\Services\WorksBuilder\Ai\Providers\ClaudeApiClient;
use App\Services\WorksBuilder\Ai\Providers\OpenAiApiClient;
use Illuminate\Support\Facades\Log;

/**
 * 명세 v11 §1.7 — AI 호출 오케스트레이터.
 *
 * Primary(OpenAI) → Fallback(Claude). Anthropic 결제 이슈 우회를 위해 OpenAI 가 primary.
 * AppServiceProvider 의 DesignSystemAiService, AiOrchestrator 와 정책 통일.
 * 401/403 Fatal 은 즉시 중단.
 */
class AiCallOrchestrator
{
    public function __construct(
        private ClaudeApiClient $claude,
        private OpenAiApiClient $openai,
        private AiCallLogger $logger,
    ) {}

    /**
     * @return array{result: AiResult, log: AiCallLog}
     */
    public function call(
        Task $task,
        string $stage,
        ?int $reviewRound,
        InternalPrompt $prompt,
    ): array {
        $log = $this->logger->startLog($task, $stage, $reviewRound, $prompt, primaryProvider: 'openai');

        // Primary: OpenAI
        try {
            $result = $this->openai->generate($prompt->system_prompt ?? '', $prompt->user_prompt);
            $log = $this->logger->finalize($log, $result);
            return ['result' => $result, 'log' => $log];
        } catch (AiAttemptException $e) {
            $this->logger->recordPrimaryFailure($log, $e);

            Log::warning('[WorksBuilder] OpenAI primary failed', [
                'task_id'   => $task->id,
                'log_id'    => $log->id,
                'status'    => $e->statusCode,
                'message'   => $e->getMessage(),
                'fatal'     => $e->fatal,
            ]);

            if ($e->fatal) {
                $this->logger->markFailed($log);
                throw new AiAttemptException(
                    $e->statusCode,
                    "AI 호출 실패 (OpenAI 인증/권한 오류): {$e->getMessage()}",
                    fatal: true,
                );
            }
        }

        // Fallback: Claude
        try {
            $result = $this->claude->generate($prompt->system_prompt ?? '', $prompt->user_prompt);
            $this->logger->recordFallbackUsed($log);
            $log = $this->logger->finalize($log, $result);
            return ['result' => $result, 'log' => $log];
        } catch (AiAttemptException $e) {
            $this->logger->recordFallbackFailure($log, $e);
            $this->logger->markFailed($log);

            Log::error('[WorksBuilder] Claude fallback failed', [
                'task_id'   => $task->id,
                'log_id'    => $log->id,
                'status'    => $e->statusCode,
                'message'   => $e->getMessage(),
            ]);

            throw new AiAttemptException(
                $e->statusCode,
                'AI 호출 실패 (OpenAI + Claude 모두 실패): ' . $e->getMessage(),
            );
        }
    }
}
