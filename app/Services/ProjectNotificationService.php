<?php

namespace App\Services;

use App\Mail\ProjectActivityMail;
use App\Models\Project;
use App\Models\SystemErrorLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProjectNotificationService
{
    /**
     * 프로젝트 멤버(본인 제외)에게 활동 알림 이메일 발송
     * app()->terminating() 으로 HTTP 응답 후 발송 → 요청 블로킹 없음
     */
    public function notify(
        Project $project,
        User    $actor,
        string  $eventType,
        string  $entityTitle,
        string  $url,
    ): void {
        if (!config('app.notify_email_enabled', true)) {
            return;
        }

        try {
            $recipients = $project->members()
                ->where('users.id', '!=', $actor->id)
                ->get();
        } catch (\Throwable $e) {
            Log::error('[ProjectNotification] 수신자 조회 실패 (' . $eventType . '): ' . $e->getMessage());
            SystemErrorLog::record($e);
            return;
        }

        foreach ($recipients as $recipient) {
            if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // 값을 미리 추출 — 클로저에서 모델 객체 대신 원시값 캡처
            $toEmail     = $recipient->email;
            $projectSnap = $project;
            $actorSnap   = $actor;
            $typeSnap    = $eventType;
            $titleSnap   = $entityTitle;
            $urlSnap     = $this->toAppUrl($url);

            app()->terminating(static function () use ($toEmail, $projectSnap, $actorSnap, $typeSnap, $titleSnap, $urlSnap) {
                set_time_limit(0);
                try {
                    Log::info('[ProjectNotification] 발송 시도: ' . $typeSnap . ' → ' . $toEmail);
                    $mailable = new ProjectActivityMail($projectSnap, $actorSnap, $typeSnap, $titleSnap, $urlSnap);
                    Mail::to($toEmail)->send($mailable);
                    Log::info('[ProjectNotification] 발송 완료: ' . $typeSnap . ' → ' . $toEmail);
                } catch (\Throwable $e) {
                    Log::error('[ProjectNotification] ' . $typeSnap . ' → ' . $toEmail . ': ' . $e->getMessage());
                    SystemErrorLog::record($e, 'warning');
                }
            });
        }
    }

    /**
     * 특정 사용자 ID 목록에게 알림 이메일 발송 (검토 요청 등)
     */
    public function notifySpecific(
        Project  $project,
        User     $actor,
        array    $recipientIds,
        string   $eventType,
        string   $entityTitle,
        string   $url,
        ?string  $reviewMessage = null,
    ): int {
        // 검토 요청은 사용자가 직접 실행하는 명시적 액션이므로
        // NOTIFY_EMAIL_ENABLED 플래그를 무시하고 항상 발송한다.
        // app()->terminating() 대신 즉시 동기 전송 — terminating 콜백은
        // 이전 요청 메모리가 남은 상태로 실행되어 메모리 초과를 유발한다.

        $normalizedUrl = $this->toAppUrl($url);
        $count         = 0;

        SystemErrorLog::log('info', '[ReviewRequest] 검토 요청 시작', [
            'event'      => $eventType,
            'file'       => $entityTitle,
            'actor'      => $actor->email,
            'recipients' => count($recipientIds),
        ]);

        foreach ($recipientIds as $userId) {
            $recipient = User::find($userId);
            if (!$recipient) {
                SystemErrorLog::log('warning', '[ReviewRequest] 존재하지 않는 사용자 ID: ' . $userId);
                continue;
            }
            if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                SystemErrorLog::log('warning', '[ReviewRequest] 유효하지 않은 이메일 주소: ' . $recipient->email);
                continue;
            }

            $mailOk = false;
            try {
                SystemErrorLog::log('info', '[ReviewRequest] 발송 시도 → ' . $recipient->email);
                $mailable = new ProjectActivityMail(
                    $project, $actor, $eventType,
                    $entityTitle, $normalizedUrl, $reviewMessage
                );
                Mail::to($recipient->email)->send($mailable);
                SystemErrorLog::log('info', '[ReviewRequest] 발송 완료 → ' . $recipient->email);
                $count++;
                $mailOk = true;
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'error');
            }

            // 이메일 성공 후 SMS 추가 발송 — HTTP 호출이므로 응답 후 비동기 처리
            if ($mailOk && $recipient->phone) {
                $smsPhone = $recipient->phone;
                $smsName  = $recipient->name;
                $smsMsg   = "[SupportWorks] {$actor->name}님이 '{$project->name}' 프로젝트의 '{$entityTitle}' 검토를 요청했습니다.";

                app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                    set_time_limit(0);
                    try {
                        SmsService::send($smsPhone, $smsMsg, $smsName);
                    } catch (\Throwable $e) {
                        Log::warning('[ReviewRequest][SMS] ' . $e->getMessage());
                    }
                });
            }
        }

        SystemErrorLog::log('info', '[ReviewRequest] 완료 — 성공: ' . $count . '/' . count($recipientIds));

        return $count;
    }

    private function toAppUrl(string $url): string
    {
        $appUrl = rtrim(config('app.url'), '/');
        if (!$appUrl) {
            return $url;
        }

        // AppServiceProvider에서 URL::forceRootUrl() 적용 후 route()가 이미 올바른 URL을
        // 생성하므로, scheme+host만 APP_URL 기준으로 정규화한다.
        $appScheme   = parse_url($appUrl, PHP_URL_SCHEME) ?? 'https';
        $appHost     = parse_url($appUrl, PHP_URL_HOST);
        $appBasePath = rtrim(parse_url($appUrl, PHP_URL_PATH) ?? '', '/');

        $parsed = parse_url($url);
        $path   = $parsed['path'] ?? '';
        $query  = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        // APP_URL 자체에 경로가 포함된 경우(예: .../supportworks) 중복 제거
        if ($appBasePath && str_starts_with($path, $appBasePath . '/')) {
            $path = substr($path, strlen($appBasePath));
        }

        return $appScheme . '://' . $appHost . $path . $query;
    }
}
