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
