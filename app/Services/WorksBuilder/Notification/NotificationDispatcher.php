<?php

namespace App\Services\WorksBuilder\Notification;

use App\Models\WorksBuilder\Notification;
use App\Models\WorksBuilder\NotificationSetting;
use App\Models\WorksBuilder\Task;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

/**
 * 명세 v11 §1.9 — Works Builder 단계별 알림 디스패처.
 */
class NotificationDispatcher
{
    public const STAGE_MESSAGES = [
        'started'             => ['Works Builder 작업 시작',     '작업이 시작되었습니다.'],
        'reopened'            => ['Task 재실행',                  'Task가 재실행되었습니다.'],
        'cloned'              => ['Task 복제',                    'Task가 복제되었습니다.'],
        'cancelled'           => ['Task 취소',                    'Task가 취소되었습니다.'],
        'option_input'        => ['옵션 입력 필요',                '화면 옵션 입력이 필요합니다.'],
        'spec_review'         => ['기획서 검토 필요',              'AI 생성 기획서 검토가 필요합니다.'],
        'ai_calling'          => ['AI 호출 진행 중',               'AI가 HTML을 생성하고 있습니다.'],
        'ai_fallback_used'    => ['AI 폴백 발동',                  'Claude 실패로 OpenAI로 전환되었습니다.'],
        'ai_call_failed'      => ['AI 호출 실패',                  'AI 호출이 실패했습니다. 재시도 필요.'],
        'result_confirm'      => ['AI 생성 결과 확인 필요',        'AI 생성 결과 확인이 필요합니다.'],
        'qa_review'           => ['표준 HTML 검수 필요',           '표준 HTML 검수가 필요합니다.'],
        'ng_input'            => ['NG 미스 입력 필요',             'NG 미스 항목 입력이 필요합니다.'],
        'recheck'             => ['재생성된 HTML 재검수 필요',     '재생성된 HTML 재검수가 필요합니다.'],
        'prompt_learned'      => ['체크리스트 항목 학습',           '체크리스트에 새 항목이 추가되었습니다.'],
        'package_ready'       => ['패키지 다운로드 가능',           'zip 패키지가 준비되었습니다.'],
        'complete'            => ['작업 완료',                     '작업이 완료되었습니다.'],
        'checklist_impact'    => ['체크리스트 변경 영향',          '신규 표준 항목 추가 — 영향 가능 Task가 있습니다.'],
    ];

    public function dispatchStage(
        Task $task,
        string $stageCode,
        ?int $reviewRound = null,
        ?int $overrideRecipient = null,
        ?string $deepLink = null,
        ?string $customMessage = null,
    ): ?Notification {
        if (!array_key_exists($stageCode, self::STAGE_MESSAGES)) {
            Log::warning("[WB] Unknown stage_code: {$stageCode}");
            return null;
        }

        $recipientId = $overrideRecipient ?? $task->assignee_id;
        if (!$recipientId) return null;

        [$title, $defaultMessage] = self::STAGE_MESSAGES[$stageCode];
        $message = $customMessage ?? $defaultMessage;
        if ($reviewRound !== null) {
            $message .= " ({$reviewRound}차수)";
        }

        $deepLink ??= $this->resolveDeepLink($task, $stageCode);

        $notification = null;
        if (NotificationSetting::isEnabled($recipientId, $stageCode, 'web')) {
            $notification = Notification::create([
                'recipient_id' => $recipientId,
                'task_id'      => $task->id,
                'project_id'   => $task->project_id,
                'stage_code'   => $stageCode,
                'review_round' => $reviewRound,
                'title'        => $title,
                'message'      => $message,
                'deep_link'    => $deepLink,
            ]);
        }

        if (NotificationSetting::isEnabled($recipientId, $stageCode, 'mobile_push')) {
            try {
                FcmService::notifyUser($recipientId, $title, $message, [
                    'wb_task_id'      => (string) $task->id,
                    'wb_stage_code'   => $stageCode,
                    'wb_review_round' => $reviewRound !== null ? (string) $reviewRound : '',
                    'wb_deep_link'    => $deepLink ?? '',
                ]);
            } catch (\Throwable $e) {
                Log::warning('[WB] FCM dispatch failed: '.$e->getMessage());
            }
        }

        return $notification;
    }

    private function resolveDeepLink(Task $task, string $stageCode): string
    {
        return match ($stageCode) {
            'option_input'   => route('wb.tasks.options.edit', $task, false),
            'spec_review'    => route('wb.tasks.spec-review.show', $task, false),
            'ai_calling',
            'ai_fallback_used',
            'ai_call_failed' => route('wb.tasks.ai-progress.show', $task, false),
            'result_confirm' => route('wb.tasks.result-confirm.show', $task, false),
            'qa_review',
            'ng_input',
            'recheck'        => route('wb.tasks.show', $task, false),
            'package_ready',
            'complete'       => route('wb.tasks.show', $task, false),
            default          => route('wb.tasks.show', $task, false),
        };
    }
}
