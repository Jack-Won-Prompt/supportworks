<?php

namespace App\Services;

use App\Models\User;
use App\Services\Popbill\MessageService;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * 단일 사용자(또는 휴대폰 번호)에게 SMS 1건 발송.
     * 실패해도 throw 하지 않고 false 반환 — 이메일 후속 보조 채널이므로 비차단.
     *
     * @param bool $alsoFcm true 면 SMS 발송 후 동일 내용을 FCM 으로도 발송.
     *                      호출측이 이미 자체 FCM 알림(다른 type) 을 보내고 있을 때
     *                      false 로 호출해 중복 알림을 막을 수 있다.
     */
    public static function send(User|string|null $to, string $content, ?string $receiverName = null, bool $alsoFcm = true): bool
    {
        $phone = null;
        $name  = $receiverName;
        $user  = $to instanceof User ? $to : null;

        if ($to instanceof User) {
            $phone = self::normalize($to->phone ?? null);
            $name  = $name ?? $to->name;
        } else {
            $phone = self::normalize($to);
        }

        if (!$phone) {
            // SMS는 불가하지만 사용자를 알면 FCM 푸시는 시도 (옵션)
            if ($alsoFcm) self::pushFcm($user, null, $content);
            return false;
        }

        // 팝빌 라이브러리의 PopbillBase 클래스가 클래스 로드 시점에 LINKHUB_COMM_MODE
        // 상수를 참조하므로 autoload 이전(=class_exists 호출 이전)에 반드시 정의되어야 함.
        if (!defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }

        if (!class_exists(\Linkhub\Popbill\PopbillMessaging::class)) {
            Log::warning('[SMS] linkhub/popbill 패키지가 설치되지 않아 발송을 생략합니다.');
            return false;
        }

        if (!config('popbill.LinkID') || !config('popbill.SecretKey')) {
            Log::warning('[SMS] POPBILL_ID / POPBILL_SECRET_KEY 미설정 — 발송 생략', ['to' => $phone]);
            return false;
        }

        $sent = false;
        try {
            $svc = new MessageService();
            $svc->send($phone, self::truncate($content), $name);
            $sent = true;
        } catch (\Throwable $e) {
            Log::warning('[SMS] 발송 실패: ' . $e->getMessage(), ['to' => $phone]);
        }

        // SMS 전송 후 동일 내용을 FCM 푸시로도 발송 (옵션)
        if ($alsoFcm) self::pushFcm($user, $phone, $content);

        return $sent;
    }

    /**
     * SMS 내용을 FCM 푸시로 전송.
     * User 객체가 없으면 전화번호로 사용자를 조회한다.
     */
    private static function pushFcm(?User $user, ?string $phone, string $content): void
    {
        try {
            if (!$user && $phone) {
                // DB의 phone 값에서 숫자만 추출해 정규화된 번호와 비교
                $user = User::whereRaw(
                    "REPLACE(REPLACE(REPLACE(IFNULL(phone,''),'-',''),' ',''),'+','') = ?",
                    [$phone],
                )->first();
            }
            if (!$user) {
                return; // 매칭되는 사용자 없음 — FCM 생략
            }

            $body = trim($content);
            // "[SupportWorks] ..." 접두 형식이면 접두 제거해 본문만 표시
            if (preg_match('/^\[SupportWorks\]\s*(.+)$/su', $body, $m)) {
                $body = trim($m[1]);
            }

            FcmService::notifyUser($user->id, 'SupportWorks 알림', $body, ['type' => 'sms']);
        } catch (\Throwable $e) {
            Log::warning('[SMS→FCM] 푸시 실패: ' . $e->getMessage());
        }
    }

    /**
     * 다수 사용자에게 같은 내용으로 발송 — 발송 성공 건수 반환.
     *
     * @param bool $alsoFcm self::send 에 전달. 자세한 설명은 send() 참고.
     */
    public static function sendMany(iterable $users, string $content, bool $alsoFcm = true): int
    {
        $ok = 0;
        foreach ($users as $u) {
            if (self::send($u, $content, null, $alsoFcm)) {
                $ok++;
            }
        }
        return $ok;
    }

    private static function normalize(?string $raw): ?string
    {
        if (!$raw) return null;
        $digits = preg_replace('/\D/', '', $raw);
        if (!$digits) return null;
        // 한국 휴대전화 기준 9~11자리만 허용
        if (strlen($digits) < 9 || strlen($digits) > 11) {
            return null;
        }
        return $digits;
    }

    /**
     * SMS 90byte / LMS 2000byte 제한 — 안전하게 1900자(UTF-8 멀티바이트 고려)로 자르기.
     */
    private static function truncate(string $content): string
    {
        $maxBytes = 1900;
        if (strlen($content) <= $maxBytes) return $content;
        return mb_strcut($content, 0, $maxBytes - 3, 'UTF-8') . '...';
    }
}
