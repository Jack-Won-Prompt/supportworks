<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging 발송 서비스 (HTTP v1 API)
 * - 외부 패키지 없이 서비스 계정 JWT → OAuth2 access_token → FCM v1 호출
 */
class FcmService
{
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    /** 서비스 계정 키 파일 경로 */
    private static function credentialsPath(): string
    {
        return storage_path('app/firebase/supportworks-803b4-firebase-adminsdk.json');
    }

    /** 특정 사용자에게 알림 발송 (해당 사용자의 모든 기기) */
    public static function notifyUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
        self::sendToTokens($tokens, $title, $body, $data);
    }

    /** 여러 사용자에게 알림 발송 */
    public static function notifyUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        if (empty($userIds)) return;
        $tokens = DeviceToken::whereIn('user_id', $userIds)->pluck('token')->all();
        self::sendToTokens($tokens, $title, $body, $data);
    }

    /** 토큰 목록으로 발송 */
    public static function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) return;

        try {
            $accessToken = self::accessToken();
            $projectId   = self::serviceAccount()['project_id'];
            $url         = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // data 페이로드는 문자열만 허용
            $stringData = [];
            foreach ($data as $k => $v) {
                $stringData[(string) $k] = (string) $v;
            }

            foreach ($tokens as $token) {
                $res = Http::withToken($accessToken)
                    ->timeout(15)
                    ->post($url, [
                        'message' => [
                            'token'        => $token,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data'         => $stringData,
                            'android'      => [
                                'priority'     => 'high',
                                'notification' => ['channel_id' => 'supportworks_high'],
                            ],
                        ],
                    ]);

                // 무효 토큰 정리
                if (!$res->successful()) {
                    $err = $res->json('error.status');
                    if (in_array($err, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'])) {
                        DeviceToken::where('token', $token)->delete();
                    } else {
                        Log::warning('FCM send failed', ['status' => $res->status(), 'body' => $res->body()]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('FCM error: ' . $e->getMessage());
        }
    }

    // ── OAuth2 access_token (1시간 캐시) ──────────────────────────────────
    private static function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $sa  = self::serviceAccount();
            $now = time();

            $jwtHeader = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtClaim  = self::b64url(json_encode([
                'iss'   => $sa['client_email'],
                'scope' => self::FCM_SCOPE,
                'aud'   => self::TOKEN_URI,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signingInput = "{$jwtHeader}.{$jwtClaim}";
            $signature    = '';
            openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = "{$signingInput}." . self::b64url($signature);

            $res = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$res->successful()) {
                throw new \RuntimeException('FCM 토큰 발급 실패: ' . $res->body());
            }
            return $res->json('access_token');
        });
    }

    private static function serviceAccount(): array
    {
        $path = self::credentialsPath();
        if (!file_exists($path)) {
            throw new \RuntimeException('Firebase 서비스 계정 키 파일이 없습니다: ' . $path);
        }
        return json_decode(file_get_contents($path), true);
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}