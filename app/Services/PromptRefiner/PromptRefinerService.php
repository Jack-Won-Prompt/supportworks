<?php

namespace App\Services\PromptRefiner;

use App\Models\PromptHistory;
use App\Models\PromptSession;
use App\Models\User;
use App\Services\PromptRefiner\Llm\LlmRequest;
use App\Services\PromptRefiner\Llm\LlmRouter;
use App\Services\PromptRefiner\Llm\Exceptions\AllProvidersFailedException;
use App\Services\PromptRefiner\Llm\Exceptions\LlmFatalException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromptRefinerService
{
    private const MAX_TOKENS = 2000;
    private const MAX_ROUNDS = 5;

    public function __construct(
        private SystemPromptProvider $systemPromptProvider,
        private ContextLoader $contextLoader,
        private LlmRouter $llmRouter,
    ) {}

    public function refine(User $user, array $data): array
    {
        $requestId = 'req_' . Str::random(12);

        // 세션 로드 또는 신규 생성
        if (!empty($data['session_id'])) {
            $session = PromptSession::find($data['session_id']);
            abort_if(!$session || $session->user_id !== $user->id, 404, '세션을 찾을 수 없습니다.');
            abort_if($session->isExpired(), 422, '세션이 만료되었습니다. 새로 시작해주세요.');
        } else {
            $projectId  = isset($data['project_id'])  ? (int)$data['project_id']  : null;
            $scheduleId = isset($data['schedule_id']) ? (int)$data['schedule_id'] : null;

            $session = PromptSession::newSession(
                $user->id,
                $data['mode'],
                $data['user_input'],
                $projectId,
                $scheduleId
            );
        }

        // 컨텍스트 로드
        $projectContext = null;
        if ($data['mode'] === 'project' && $session->project_id) {
            $projectContext = $this->contextLoader->load(
                $session->project_id,
                $session->schedule_id,
                $user
            );
            if ($projectContext === null) {
                abort(404, '프로젝트를 찾을 수 없거나 접근 권한이 없습니다.');
            }
        }

        // 이번 라운드 answers를 이전 rounds_data의 마지막 라운드에 기록
        $answers = $data['clarification_answers'] ?? [];
        if (!empty($answers)) {
            $rounds = $session->rounds_data ?? [];
            if (!empty($rounds)) {
                $lastIdx = count($rounds) - 1;
                if (empty($rounds[$lastIdx]['answers'])) {
                    $rounds[$lastIdx]['answers'] = $answers;
                    $session->rounds_data = $rounds;
                    $session->save();
                }
            }
        }

        // LLM 페이로드 구성
        $clarificationHistory = $this->buildClarificationHistory($session, $answers);
        $payload = array_filter([
            'user_input'            => $session->original_input,
            'mode'                  => $session->mode,
            'project_context'       => $projectContext,
            'clarification_history' => $clarificationHistory ?: null,
        ]);

        // LLM 호출 (Claude 우선, 실패 시 OpenAI 자동 전환)
        try {
            $llmResponse = $this->llmRouter->execute(
                new LlmRequest(
                    systemPrompt: $this->systemPromptProvider->get(),
                    userMessage:  json_encode($payload, JSON_UNESCAPED_UNICODE),
                    maxTokens:    self::MAX_TOKENS,
                    temperature:  0.3,
                ),
                requestId: $requestId,
            );
        } catch (LlmFatalException $e) {
            Log::error('PromptRefiner: LLM 치명적 오류 (4xx)', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
            abort(422, '잘못된 요청으로 웍스 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
        } catch (AllProvidersFailedException $e) {
            Log::error('PromptRefiner: 모든 LLM 프로바이더 실패', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
            abort(503, '웍스 서비스에 일시적 장애가 있습니다. 잠시 후 다시 시도해주세요.');
        }

        $result = json_decode($llmResponse->content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('PromptRefiner: LLM 응답 JSON 파싱 실패', [
                'request_id' => $requestId,
                'raw'        => substr($llmResponse->content, 0, 500),
            ]);
            abort(422, '웍스 응답을 처리하는 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        $elapsedMs = $llmResponse->elapsedMs;
        $status    = $result['status'] ?? null;

        if ($status === 'needs_clarification') {
            if ($session->current_round > self::MAX_ROUNDS) {
                Log::warning('PromptRefiner: 최대 명확화 라운드 초과', [
                    'session_id' => $session->session_id,
                    'request_id' => $requestId,
                ]);
                abort(422, '명확화 라운드 한도를 초과했습니다. 더 구체적인 입력으로 새로 시작해주세요.');
            }

            $questions = $result['questions'] ?? [];
            $rounds    = $session->rounds_data ?? [];
            $rounds[]  = [
                'round'     => $session->current_round,
                'questions' => $questions,
                'answers'   => [],
                'timestamp' => now()->toIso8601String(),
            ];
            $session->rounds_data   = $rounds;
            $session->current_round += 1;
            $session->save();

            return [
                'session_id'       => $session->session_id,
                'status'           => 'needs_clarification',
                'task_type'        => $result['task_type'] ?? 'unknown',
                'round'            => $session->current_round - 1,
                'context_strength' => $projectContext['context_strength'] ?? 'none',
                'questions'        => $questions,
                'metadata'         => ['request_id' => $requestId, 'elapsed_ms' => $elapsedMs],
            ];
        }

        if ($status === 'refined') {
            $fallbackOccurred = $llmResponse->fallbackReason !== null;

            $resultMetadata = array_merge($result['metadata'] ?? [], [
                'context_strength'         => $projectContext['context_strength'] ?? 'none',
                'referenced_history_count' => count($projectContext['previous_prompts'] ?? []),
                'referenced_schedule_id'   => $session->schedule_id,
                'provider_used'            => $llmResponse->providerUsed,
                'model_used'               => $llmResponse->modelUsed,
                'fallback_occurred'        => $fallbackOccurred,
            ]);

            $history = PromptHistory::saveResult(
                userId:              $user->id,
                sessionId:           $session->session_id,
                mode:                $session->mode,
                projectId:           $session->project_id,
                scheduleId:          $session->schedule_id,
                taskType:            $result['task_type'] ?? 'unknown',
                originalInput:       $session->original_input,
                clarificationRounds: $session->rounds_data ?? [],
                refinedPrompt:       $result['refined_prompt'] ?? '',
                metadata:            $resultMetadata,
                llmModel:            $llmResponse->modelUsed,
                totalTokens:         $llmResponse->totalTokens,
                elapsedMs:           $elapsedMs,
                providerUsed:        $llmResponse->providerUsed,
                fallbackReason:      $llmResponse->fallbackReason,
            );

            $session->complete();

            return [
                'session_id'       => $session->session_id,
                'status'           => 'refined',
                'task_type'        => $result['task_type'] ?? 'unknown',
                'history_id'       => $history->history_id,
                'context_strength' => $projectContext['context_strength'] ?? 'none',
                'refined_prompt'   => $result['refined_prompt'] ?? '',
                'metadata'         => array_merge($resultMetadata, [
                    'request_id' => $requestId,
                    'elapsed_ms' => $elapsedMs,
                    'llm_model'  => $llmResponse->modelUsed,
                ]),
            ];
        }

        Log::error('PromptRefiner: 예상치 못한 LLM status', [
            'status'     => $status,
            'request_id' => $requestId,
        ]);
        abort(422, '웍스 응답을 처리하는 중 오류가 발생했습니다. 다시 시도해주세요.');
    }

    private function buildClarificationHistory(PromptSession $session, array $currentAnswers): array
    {
        $history = [];
        foreach ($session->rounds_data ?? [] as $round) {
            foreach ($round['questions'] ?? [] as $q) {
                $answer = collect($round['answers'] ?? [])->firstWhere('question_id', $q['id']);
                if (!$answer) {
                    $answer = collect($currentAnswers)->firstWhere('question_id', $q['id']);
                }
                $history[] = [
                    'question_id' => $q['id'],
                    'question'    => $q['question'],
                    'user_answer' => $answer['answer'] ?? '',
                ];
            }
        }
        return $history;
    }
}
