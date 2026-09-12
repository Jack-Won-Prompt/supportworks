<?php

use Illuminate\Support\Facades\Broadcast;

// 기본 사용자 채널
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user && (int) $user->id === (int) $id;
});

// 대화 채널: 웹 사용자(참여자) 또는 관리자
// guards 옵션으로 web·admin guard 순서대로 시도
Broadcast::channel('conversation.{id}', function ($user, $id) {
    if ($user instanceof \App\Models\User) {
        // 웹 사용자: 참여자이면 타입 무관하게 허용 (일반 메시지 + 문의)
        return \App\Models\Conversation::where('id', $id)
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->exists();
    }
    if ($user instanceof \App\Models\AdminUser) {
        // 관리자: inquiry 타입 대화만 허용
        return \App\Models\Conversation::where('id', $id)
            ->where('type', 'inquiry')
            ->exists();
    }
    return false;
}, ['guards' => ['web', 'admin']]);

// 관리자 개인 알림 채널 (신규 문의, 메시지 수신)
// admin guard만 사용 → retrieveUser()가 AdminUser 반환 → 403 방지
Broadcast::channel('admin.{id}', function ($user, $id) {
    return $user instanceof \App\Models\AdminUser && $user->id === (int) $id;
}, ['guards' => ['admin']]);

// 웹 상담원 개인 채널
Broadcast::channel('agent.{userId}', function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});

// 사용자 개인 알림 채널 (관리자 발송 메시지 수신)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user instanceof \App\Models\User && $user->id === (int) $userId;
}, ['guards' => ['web']]);

// 협업: Presence 채널 (온라인 사용자 목록)
Broadcast::channel('collab', function ($user) {
    if (!$user instanceof \App\Models\User) return false;
    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
}, ['guards' => ['web']]);

// 협업: 개인 알림 채널 (요청/수락/거절 수신)
Broadcast::channel('collab-user.{userId}', function ($user, $userId) {
    return $user instanceof \App\Models\User && $user->id === (int) $userId;
}, ['guards' => ['web']]);

// AI 분석 세션 채널 (status.updated)
Broadcast::channel('analysis-session.{sessionId}', function ($user, $sessionId) {
    if (!$user instanceof \App\Models\User) return false;
    return \App\Models\AnalysisSession::where('id', $sessionId)
        ->whereHas('project.projectMembers', fn($q) => $q->where('user_id', $user->id))
        ->exists();
}, ['guards' => ['web']]);

// 협업: 세션 채널 (navigate/permission/cursor/ended)
Broadcast::channel('collab-session.{sessionKey}', function ($user, $sessionKey) {
    if (!$user instanceof \App\Models\User) return false;
    return \App\Models\CollabSession::where('session_key', $sessionKey)
        ->where(function ($q) use ($user) {
            $q->where('initiator_id', $user->id)->orWhere('participant_id', $user->id);
        })
        ->where('status', 'active')
        ->exists();
}, ['guards' => ['web']]);

// ── AI Works: Reverb 커넥션 전용 채널 등록 ──────────────────────────────
// 주의: 평범한 Broadcast::channel() 은 기본 커넥션(pusher) 인스턴스에만 등록된다.
//      Broadcaster::$channels 는 인스턴스별 배열이고 BroadcastManager::resolve() 가 복사하지 않으므로,
//      reverb 인스턴스에 따로 등록하지 않으면 /aiw/broadcasting/auth 가 항상 403 을 반환한다.
//
// 가드 필수: 이 파일은 booted() 에서 매 부팅마다 require 된다(HTTP·artisan·큐 잡 전부).
//      Broadcast::connection('reverb') 는 그 자리에서 드라이버를 resolve → new Pusher(key, secret, app_id)
//      까지 간다. config/broadcasting.php 의 reverb 커넥션은 Laravel 기본값으로 이미 존재하므로
//      "커넥션 미정의" 예외로 걸러지지 않고, REVERB_* 미설정이면 null 이 non-nullable string 파라미터로
//      넘어가 TypeError → 앱 전체 부팅 실패가 된다. key/secret/app_id 세 값을 모두 확인한다.
$aiwReverb = config('broadcasting.connections.reverb');

if (filled($aiwReverb['key'] ?? null) && filled($aiwReverb['secret'] ?? null) && filled($aiwReverb['app_id'] ?? null)) {
    // job 상세 화면의 실시간 구독.
    //
    // 권한 판단은 AiwJobPolicy 에 맡긴다. 여기에 조건을 따로 적으면 정책이
    // 바뀔 때 이 채널만 옛 규칙으로 남아, 화면은 막혔는데 로그는 그대로
    // 흘러가는 상태가 된다(멤버 기준이던 시절의 잔재였다).
    Broadcast::connection('reverb')->channel('aiw.job.{jobId}', function ($user, $jobId) {
        if (! $user instanceof \App\Models\User) {
            return false;
        }

        $job = \App\Models\AiWork\AiwJob::find($jobId);

        return $job !== null && $user->can('view', $job);
    }, ['guards' => ['web']]);

    // aiw.agent.* 는 여기 등록하지 않는다. 데몬은 user 가 없어 콜백 방식을 탈 수 없고,
    // /api/aiw/broadcasting/auth 에서 토큰을 확인한 뒤 직접 서명한다.
} else {
    // 이 경고가 없으면 "가드로 건너뜀"과 "권한 없음"이 둘 다 403 이라 구분이 안 된다.
    // channels.php 는 매 요청·artisan·큐 잡마다 실행되므로 시간당 1회로 묶는다.
    // 스토어는 file 고정: 이 프로젝트는 CACHE_STORE=database 라 기본 스토어를 쓰면 부팅 시점에 DB 를 타고,
    // cache 테이블이 없는 신규 클론에서는 부팅이 죽어 migrate 조차 못 돌게 된다.
    try {
        if (\Illuminate\Support\Facades\Cache::store('file')->add('aiw:reverb-unconfigured-warned', 1, 3600)) {
            \Illuminate\Support\Facades\Log::warning('AI Works: REVERB_APP_KEY/SECRET/APP_ID 미설정으로 reverb 채널 등록을 건너뜀. AI Works 화면의 실시간 구독이 403 이 된다.');
        }
    } catch (\Throwable $e) {
        // 캐시 백엔드가 실패해도 부팅을 막지 않는다. 경고 한 줄보다 부팅이 우선이다.
    }
}
