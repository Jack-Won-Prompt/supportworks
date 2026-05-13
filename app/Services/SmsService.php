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
     */
    public static function send(User|string|null $to, string $content, ?string $receiverName = null): bool
    {
        $phone = null;
        $name  = $receiverName;

        if ($to instanceof User) {
            $phone = self::normalize($to->phone ?? null);
            $name  = $name ?? $to->name;
        } else {
            $phone = self::normalize($to);
        }

        if (!$phone) {
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

        try {
            $svc = new MessageService();
            $svc->send($phone, self::truncate($content), $name);
            return true;
        } catch (\Throwable $e) {
            Log::warning('[SMS] 발송 실패: ' . $e->getMessage(), ['to' => $phone]);
            return false;
        }
    }

    /**
     * 다수 사용자에게 같은 내용으로 발송 — 발송 성공 건수 반환.
     */
    public static function sendMany(iterable $users, string $content): int
    {
        $ok = 0;
        foreach ($users as $u) {
            if (self::send($u, $content)) {
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
