<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;
use App\Models\User;
use App\Services\FcmService;
use App\Services\SmsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * AiFixJob 상태 변화 시 관리자에게 알림을 보낸다.
 *
 * 3채널 발송 (FCM / Email / SMS). 각 채널은 독립적으로 try/catch — 한 채널 실패가
 * 다른 채널 발송을 막지 않는다. 정책상 발송 대상이 아니면 모두 침묵.
 *
 * 책임 분리:
 *   buildPayload(job)        FCM 페이로드 (순수 함수)
 *   adminUserIds()           role=admin 사용자 id 목록
 *   adminRecipients()        role=admin 사용자 Collection (email/phone 포함)
 *   shouldNotify(status)     이 상태에서 알림을 보내야 하는지 정책 판정
 *   notify(job)              실제 발송 — 발송한 관리자 수 반환
 *
 *   sendFcm / sendEmail / sendSms  — protected, 테스트에서 override 가능
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

    /** role='admin' 인 웹 사용자 Collection (email/phone 등 채널별 필요). */
    public function adminRecipients(): Collection
    {
        return User::where('role', 'admin')->get();
    }

    /**
     * 3채널 (FCM + Email + SMS) 발송. 정책에 해당하지 않으면 모두 침묵.
     * 발송 대상이 된 관리자 수를 반환 (실제 발송 성공 수가 아님).
     */
    public function notify(AiFixJob $job): int
    {
        if (!$this->shouldNotify($job->status)) return 0;

        $admins = $this->adminRecipients();
        if ($admins->isEmpty()) return 0;

        $p = $this->buildPayload($job);

        $this->sendFcm($admins, $p);
        $this->sendEmail($admins, $p);
        $this->sendSms($admins, $p);

        return $admins->count();
    }

    // ── 채널별 발송 (각자 자기 try/catch, 한 채널 실패가 다른 채널 막지 않음) ────

    protected function sendFcm(Collection $admins, array $p): void
    {
        try {
            FcmService::notifyUsers(
                $admins->pluck('id')->all(),
                $p['title'],
                $p['body'],
                $p['data'],
            );
        } catch (\Throwable $e) {
            Log::warning('[AiFixNotifier] FCM 발송 실패: ' . $e->getMessage());
        }
    }

    protected function sendEmail(Collection $admins, array $p): void
    {
        $body = $this->buildEmailBody($p);
        foreach ($admins as $admin) {
            if (empty($admin->email)) continue;
            try {
                Mail::raw($body, function ($m) use ($admin, $p) {
                    $m->to($admin->email, $admin->name ?? null)
                      ->subject($p['title']);
                });
            } catch (\Throwable $e) {
                Log::warning("[AiFixNotifier] email to {$admin->email} 실패: " . $e->getMessage());
            }
        }
    }

    protected function sendSms(Collection $admins, array $p): void
    {
        // SmsService 가 자체적으로 FCM 도 보내는 부수효과가 있어 alsoFcm=false 로 차단.
        // FCM 은 우리가 위에서 sendFcm() 으로 정확한 ai_fix_review 페이로드로 이미 보냈다.
        $content = "[SupportWorks] {$p['title']}\n{$p['body']}";
        try {
            SmsService::sendMany($admins, $content, alsoFcm: false);
        } catch (\Throwable $e) {
            Log::warning('[AiFixNotifier] SMS 발송 실패: ' . $e->getMessage());
        }
    }

    protected function buildEmailBody(array $p): string
    {
        $jobUrl = ''; // 추후: 웹 admin 의 상세 URL (예: APP_URL . '/admin/ai-fix-jobs/' . $p['data']['job_id'])
        return implode("\n", array_filter([
            $p['body'],
            '',
            'Job ID: '   . $p['data']['job_id'],
            'Status: '   . $p['data']['status'],
            'Decision: ' . ($p['data']['decision'] ?: '-'),
            'Error ID: ' . ($p['data']['error_id'] ?: '-'),
            $jobUrl !== '' ? "\nDetail: $jobUrl" : null,
        ], fn($x) => $x !== null));
    }
}