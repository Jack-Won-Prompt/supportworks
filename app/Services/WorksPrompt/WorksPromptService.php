<?php

namespace App\Services\WorksPrompt;

use App\Models\PromptHistory;
use App\Models\PromptSession;
use App\Models\User;
use App\Services\Llm\Exceptions\AllProvidersFailedException;
use App\Services\Llm\Exceptions\LlmFatalException;
use App\Services\Llm\LlmRequest;
use App\Services\Llm\LlmRouter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 웍스 프롬프트 — 프로젝트 인지 Q&A 어시스턴트.
 *
 * 동작:
 *   - 일반 모드 (project_id=null): 1회 LLM 호출로 직답.
 *   - 프로젝트 모드 (project_id 있음): 최초 호출 시 AI가 기획서/메뉴 데이터를 분석.
 *     모호한 부분이 있으면 1라운드 명확화 질문 → 사용자 답변 → 최종 답변.
 *     모호하지 않으면 즉시 답변.
 *
 * 명확화 라운드 제약:
 *   - 프로젝트 모드에서만 허용
 *   - 최대 1라운드 (이미 clarification_history가 있으면 무조건 직답)
 */
class WorksPromptService
{
    private const MAX_TOKENS = 2400;
    private const MAX_ROUNDS = 1;

    public function __construct(
        private SystemPromptProvider $systemPromptProvider,
        private PlanContextLoader $contextLoader,
        private LlmRouter $llmRouter,
    ) {}

    /**
     * @param array{
     *   user_input: string,
     *   project_id?: int|null,
     *   session_id?: string|null,
     *   clarification_answers?: array<int, array{question_id:string, answer:string}>
     * } $data
     */
    public function refine(User $user, array $data): array
    {
        $requestId = 'req_' . Str::random(12);
        $projectId = !empty($data['project_id']) ? (int) $data['project_id'] : null;

        // 프로젝트 컨텍스트 로드 (선택적)
        $projectContext = null;
        if ($projectId !== null) {
            $projectContext = $this->contextLoader->load($projectId, $user);
            if ($projectContext === null) {
                abort(404, '프로젝트를 찾을 수 없거나 접근 권한이 없습니다.');
            }
        }

        // 세션 로드 또는 생성 (명확화 라운드 추적용)
        if (!empty($data['session_id'])) {
            $session = PromptSession::find($data['session_id']);
            abort_if(!$session || $session->user_id !== $user->id, 404, '세션을 찾을 수 없습니다.');
            abort_if($session->isExpired(), 422, '세션이 만료되었습니다. 새로 질문해주세요.');
        } else {
            $session = PromptSession::newSession(
                userId:     $user->id,
                mode:       $projectId !== null ? 'project' : 'general',
                input:      $data['user_input'],
                projectId:  $projectId,
                scheduleId: null,
            );
        }

        // 명확화 답변을 직전 라운드 슬롯에 기록
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

        // 이미 1라운드 완료된 경우 LLM에 명확화 금지 신호
        $alreadyClarified = !empty($session->rounds_data);

        // LLM 페이로드
        $clarificationHistory = $this->buildClarificationHistory($session, $answers);
        $payload = array_filter([
            'user_input'            => $session->original_input,
            'project_context'       => $projectContext,
            'clarification_history' => $clarificationHistory ?: null,
            // 명확화 잔여 횟수 (프로젝트 모드 + 미사용 시 1, 아니면 0)
            'remaining_clarifications' => ($projectId !== null && !$alreadyClarified) ? self::MAX_ROUNDS : 0,
        ], fn($v) => $v !== null);

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
            Log::error('WorksPrompt: LLM 치명적 오류 (4xx)', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
            abort(422, '잘못된 요청으로 웍스 처리 중 오류가 발생했습니다. 다시 시도해주세요.');
        } catch (AllProvidersFailedException $e) {
            Log::error('WorksPrompt: 모든 LLM 프로바이더 실패', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
            abort(503, '웍스 서비스에 일시적 장애가 있습니다. 잠시 후 다시 시도해주세요.');
        }

        $result = json_decode($llmResponse->content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('WorksPrompt: LLM 응답 JSON 파싱 실패', [
                'request_id' => $requestId,
                'raw'        => substr($llmResponse->content, 0, 500),
            ]);
            abort(422, '웍스 응답을 처리하는 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        $status   = $result['status'] ?? null;
        $taskType = (string) ($result['task_type'] ?? 'other');
        $elapsedMs = $llmResponse->elapsedMs;

        // ── 명확화 질문 (프로젝트 모드 + 1라운드 미만일 때만 허용) ──
        if ($status === 'needs_clarification' && $projectId !== null && !$alreadyClarified) {
            $questions = $result['questions'] ?? [];
            if (empty($questions)) {
                Log::warning('WorksPrompt: needs_clarification 응답에 질문이 비어있음', ['request_id' => $requestId]);
                // 폴백: 명확화 무시하고 일반 답변 분기로 fallthrough
            } else {
                $rounds = $session->rounds_data ?? [];
                $rounds[] = [
                    'round'     => $session->current_round,
                    'questions' => $questions,
                    'answers'   => [],
                    'timestamp' => now()->toIso8601String(),
                ];
                $session->rounds_data   = $rounds;
                $session->current_round += 1;
                $session->save();

                return [
                    'session_id' => $session->session_id,
                    'status'     => 'needs_clarification',
                    'mode'       => 'project',
                    'task_type'  => $taskType,
                    'questions'  => $questions,
                    'metadata'   => [
                        'request_id' => $requestId,
                        'elapsed_ms' => $elapsedMs,
                        'llm_model'  => $llmResponse->modelUsed,
                    ],
                ];
            }
        }

        // ── 직답 (status=answered 또는 answer 필드 존재) ──
        $answer = (string) ($result['answer'] ?? '');
        if ($answer === '') {
            Log::error('WorksPrompt: 빈 답변 반환', [
                'request_id' => $requestId,
                'status'     => $status,
                'raw'        => substr($llmResponse->content, 0, 500),
            ]);
            abort(422, '웍스 응답이 비어 있습니다. 다시 시도해주세요.');
        }

        $resultMetadata = array_merge($result['metadata'] ?? [], [
            'provider_used'      => $llmResponse->providerUsed,
            'model_used'         => $llmResponse->modelUsed,
            'fallback_occurred'  => $llmResponse->fallbackReason !== null,
            'has_plan'           => isset($projectContext['planning_doc']) && $projectContext['planning_doc'] !== null,
            'history_referenced' => isset($projectContext['previous_prompts']) ? count($projectContext['previous_prompts']) : 0,
            'clarification_used' => $alreadyClarified,
        ]);

        $history = PromptHistory::saveResult(
            userId:              $user->id,
            sessionId:           $session->session_id,
            mode:                $projectId !== null ? 'project' : 'general',
            projectId:           $projectId,
            scheduleId:          null,
            taskType:            $taskType,
            originalInput:       $session->original_input,
            clarificationRounds: $session->rounds_data ?? [],
            refinedPrompt:       $answer,
            metadata:            $resultMetadata,
            llmModel:            $llmResponse->modelUsed,
            totalTokens:         $llmResponse->totalTokens,
            elapsedMs:           $elapsedMs,
            providerUsed:        $llmResponse->providerUsed,
            fallbackReason:      $llmResponse->fallbackReason,
        );

        $session->complete();

        return [
            'session_id' => $session->session_id,
            'status'     => 'answered',
            'mode'       => $projectId !== null ? 'project' : 'general',
            'task_type'  => $taskType,
            'history_id' => $history->history_id,
            'answer'     => $answer,
            'metadata'   => array_merge($resultMetadata, [
                'request_id' => $requestId,
                'elapsed_ms' => $elapsedMs,
                'llm_model'  => $llmResponse->modelUsed,
            ]),
        ];
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
