<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WebHandoverController extends Controller
{
    /**
     * GET /auth/handover?token=...&redirect=...
     *
     * 트레이 앱이 발급한 일회용 토큰으로 웹 세션을 자동 생성한 뒤
     * redirect 파라미터로 이동시킨다.
     *
     * - 토큰은 일회용 (사용 즉시 삭제)
     * - redirect 는 같은 도메인 내부 path 만 허용 (open redirect 방어)
     */
    public function consume(Request $request): RedirectResponse
    {
        $token    = (string) $request->query('token', '');
        $redirect = (string) $request->query('redirect', '/');

        if ($token === '') {
            return $this->fail('handover_invalid');
        }

        $cacheKey = 'handover:' . $token;
        $data     = Cache::pull($cacheKey); // 일회용: 가져오는 즉시 삭제

        if (!is_array($data) || empty($data['user_id'])) {
            return $this->fail('handover_expired');
        }

        $user = User::find((int) $data['user_id']);
        if (!$user) {
            return $this->fail('handover_user_missing');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->to($this->sanitizeRedirect($redirect));
    }

    private function sanitizeRedirect(string $redirect): string
    {
        // 절대 URL / 스킴 / 슬래시 두 개로 시작하는 경로 거부 → 항상 같은 호스트 내 path
        if ($redirect === '' || !str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return '/';
        }
        // CRLF 방어
        if (str_contains($redirect, "\r") || str_contains($redirect, "\n")) {
            return '/';
        }
        return $redirect;
    }

    private function fail(string $reason): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'handover' => '자동 로그인 링크가 유효하지 않거나 만료되었습니다.',
        ])->with('handover_error', $reason);
    }
}
