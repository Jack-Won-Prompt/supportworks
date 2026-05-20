<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;
use App\Models\User;
use App\Services\FcmService;

/**
 * AiFixJob 상태 변화 시 관리자에게 FCM 알림을 보낸다.
 *
 * 책임 분리:
 *   buildPayload(job)   — FCM 페이로드 구성 (순수 함수, 테스트 쉬움)
 *   adminUserIds()      — DB 에서 role='admin' 웹사용자 id 수집
 *   shouldNotify(status)— 이 상태에서 알림을 보내야 하는지 정책 판정
 *   notify(job)         — 실제 FCM 발송 (FcmService 호출)
 */
class AiFixNotifier
{
    /** 알림을 발송할 상태 화이트리스트. 그 외 상태는 침묵. */
    private const NOTIFY_STATUSES = [
        AiFixJob::STATUS_AWAITING_APPROVAL,    // 관리자 승인 필요
        AiFixJob::STATUS_BLOCKED,              // 사람 수동 처리 필요
        AiFixJob::STATUS_TESTS_FAILED,         // 테스트 실패 — 알림
        AiFixJob::STATUS_DEPLOYED,             // 배포 성공 (확인용)
        AiFixJob::STATUS_DEPLOY_FAILED,        // 배포 실패 (긴급)
        AiFixJob::STATUS_ROLLED_BACK,          // 자동 롤백됨 (긴급)
    ];

    public function shouldNotify(string $status): bool
    {
        return in_array($status, self::NOTIFY_STATUSES, true);
    }

    /**
     * FCM 발송에 쓰일 title/body/data 페이로드. data 의 모든 값은 문자열이어야 한다.
     */
    public function buildPayload(AiFixJob $job): array
    {
        $title = match ($job->status) {
            AiFixJob::STATUS_AWAITING_APPROVAL => 'AI 수정 검토 요청',
            AiFixJob::STATUS_BLOCKED           => 'AI 수정 차단 — 사람 처리 필요',
            AiFixJob::STATUS_TESTS_FAILED      => 'AI 수정 테스트 실패',
            AiFixJob::STATUS_DEPLOYED          => 'AI 수정 배포 완료',
            AiFixJob::STATUS_DEPLOY_FAILED     => 'AI 수정 배포 실패',
            AiFixJob::STATUS_ROLLED_BACK       => 'AI 수정 자동 롤백',
            default                            => 'AI Fix 업데이트',
        };

        $err = $job->systemErrorLog;
        $bodySource = $job->proposed_fix_summary
            ?: trim(($err?->exception ?? '') . ': ' . ($err?->message ?? ''));
        $body = mb_substr($bodySource, 0, 160);

        return [
            'title' => $title,
            'body'  => $body,
            'data'  => [
                'type'     => 'ai_fix_review',
                'job_id'   => (string) $job->id,
                'status'   => (string) $job->status,
                'decision' => (string) ($job->decision ?? ''),
                'error_id' => (string) ($job->system_error_log_id ?? ''),
            ],
        ];
    }

    /** role='admin' 인 웹 사용자 id 목록. */
    public function adminUserIds(): array
    {
        return User::where('role', 'admin')->pluck('id')->all();
    }

    /**
     * 실제 발송. 발송한 관리자 수 반환. 정책에 해당하지 않는 상태이거나
     * 관리자가 없으면 0 반환하고 FCM 호출하지 않음.
     */
    public function notify(AiFixJob $job): int
    {
        if (!$this->shouldNotify($job->status)) return 0;

        $ids = $this->adminUserIds();
        if (empty($ids)) return 0;

        $p = $this->buildPayload($job);
        FcmService::notifyUsers($ids, $p['title'], $p['body'], $p['data']);
        return count($ids);
    }
}