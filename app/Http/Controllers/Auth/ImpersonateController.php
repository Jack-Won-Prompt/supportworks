<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ImpersonateController extends Controller
{
    public function login(string $token)
    {
        $userId = Cache::pull("impersonate_{$token}");

        if (!$userId) {
            abort(403, '유효하지 않거나 만료된 링크입니다.');
        }

        $user = User::findOrFail($userId);

        // Auth::login() 은 내부적으로 session()->migrate(true) 를 호출해
        // 세션 ID를 재생성한다. 이 경우 관리자 패널 탭이 갖고 있는 이전 세션
        // 쿠키가 무효화되어 두 번째 이후 impersonate 요청이 403/401로 실패한다.
        // 세션 재생성 없이 사용자 인증 키만 직접 기록한다.
        session()->put(Auth::guard()->getName(), $user->getAuthIdentifier());

        // 가장 세션 표시 — 배너 렌더링에 사용
        session([
            'impersonating'      => true,
            'impersonating_name' => $user->name,
            'impersonating_email'=> $user->email,
        ]);

        return redirect()->route('dashboard');
    }
}
