<?php

namespace App\Http\Controllers;

use App\Events\CollabEvent;
use App\Models\CollabSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CollabController extends Controller
{
    private const ONLINE_TTL_SECS  = 130; // 2분 + 여유
    private const CACHE_STORE_SECS = 300;

    private function cacheKey(int $companyGroupId): string
    {
        return 'collab_online_users_' . $companyGroupId;
    }

    // 온라인 사용자 목록 조회 + 현재 사용자 등록
    public function online()
    {
        $me  = auth()->user();
        $now = now()->timestamp;
        $key = $this->cacheKey($me->company_group_id);

        $users = $this->readUsers($key, $now);
        $users[$me->id] = ['id' => $me->id, 'name' => $me->name, 'ts' => $now];
        Cache::put($key, $users, self::CACHE_STORE_SECS);

        return response()->json([
            'users' => array_values($users),
        ]);
    }

    // 하트비트 (주기적으로 온라인 유지)
    public function heartbeat()
    {
        $me  = auth()->user();
        $now = now()->timestamp;
        $key = $this->cacheKey($me->company_group_id);

        $users = $this->readUsers($key, $now);
        $users[$me->id] = ['id' => $me->id, 'name' => $me->name, 'ts' => $now];
        Cache::put($key, $users, self::CACHE_STORE_SECS);

        return response()->json(['ok' => true]);
    }

    // 캐시 읽기 + user ID로 재인덱싱 + 만료 제거
    private function readUsers(string $key, int $now): array
    {
        $raw = Cache::get($key, []);

        // user ID 키로 정규화 (0-indexed 등 비정상 포맷 복구)
        $indexed = [];
        foreach ($raw as $u) {
            if (is_array($u) && isset($u['id']) && ($now - ($u['ts'] ?? 0)) < self::ONLINE_TTL_SECS) {
                $indexed[(int) $u['id']] = $u;
            }
        }
        return $indexed;
    }

    // 세션 요청 전송
    public function sendRequest(Request $request)
    {
        $request->validate(['participant_id' => 'required|integer|exists:users,id']);

        $me = auth()->user();
        $participantId = (int) $request->participant_id;

        if ($participantId === $me->id) {
            return response()->json(['error' => '자기 자신에게는 요청할 수 없습니다.'], 422);
        }

        // 기존 진행 중 세션 정리
        CollabSession::where(function ($q) use ($me) {
            $q->where('initiator_id', $me->id)->orWhere('participant_id', $me->id);
        })->whereIn('status', ['pending', 'active'])->update(['status' => 'ended']);

        $session = CollabSession::create([
            'session_key'    => Str::random(32),
            'initiator_id'   => $me->id,
            'participant_id' => $participantId,
            'status'         => 'pending',
            'permission'     => 'view',
            'current_url'    => $request->current_url,
        ]);

        broadcast(new CollabEvent(
            "collab-user.{$participantId}",
            'request',
            [
                'session_key'    => $session->session_key,
                'initiator_id'   => $me->id,
                'initiator_name' => $me->name,
                'current_url'    => $request->current_url,
            ]
        ));

        return response()->json([
            'ok'          => true,
            'session_key' => $session->session_key,
        ]);
    }

    // 요청 수락/거절
    public function respond(Request $request, string $sessionKey)
    {
        $request->validate(['accept' => 'required|boolean']);

        $session = CollabSession::where('session_key', $sessionKey)
            ->where('participant_id', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        if (!$request->accept) {
            $session->update(['status' => 'ended']);

            broadcast(new CollabEvent(
                "collab-user.{$session->initiator_id}",
                'declined',
                ['session_key' => $sessionKey]
            ));

            return response()->json(['ok' => true]);
        }

        $session->update(['status' => 'active']);

        $absoluteUrl = $this->toAbsoluteUrl($session->current_url);

        broadcast(new CollabEvent(
            "collab-user.{$session->initiator_id}",
            'accepted',
            [
                'session_key'      => $sessionKey,
                'participant_name' => auth()->user()->name,
                'current_url'      => $absoluteUrl,
            ]
        ));

        return response()->json([
            'ok'          => true,
            'session_key' => $sessionKey,
            'current_url' => $absoluteUrl,
        ]);
    }

    // 현재 URL 동기화 (host → participant)
    public function navigate(Request $request, string $sessionKey)
    {
        $request->validate(['url' => 'required|string|max:1000']);

        $session = CollabSession::where('session_key', $sessionKey)
            ->where('initiator_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        $session->update(['current_url' => $request->url]);

        broadcast(new CollabEvent(
            "collab-session.{$sessionKey}",
            'navigate',
            ['url' => $this->toAbsoluteUrl($request->url)]
        ));

        return response()->json(['ok' => true]);
    }

    // 권한 변경
    public function changePermission(Request $request, string $sessionKey)
    {
        $request->validate(['permission' => 'required|in:view,guide,control']);

        $session = CollabSession::where('session_key', $sessionKey)
            ->where('initiator_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        $session->update(['permission' => $request->permission]);

        broadcast(new CollabEvent(
            "collab-session.{$sessionKey}",
            'permission',
            ['permission' => $request->permission]
        ));

        return response()->json(['ok' => true]);
    }

    // 커서 위치 / 클릭 안내 브로드캐스트
    public function cursor(Request $request, string $sessionKey)
    {
        $request->validate([
            'x'             => 'required|numeric',
            'y'             => 'required|numeric',
            'guide_click'   => 'boolean',
            'remote_action' => 'boolean',
            'selector'      => 'nullable|string|max:200',
        ]);

        $me = auth()->id();

        $exists = CollabSession::where('session_key', $sessionKey)
            ->where(function ($q) use ($me) {
                $q->where('initiator_id', $me)->orWhere('participant_id', $me);
            })
            ->where('status', 'active')
            ->exists();

        if (!$exists) return response()->json(['ok' => false], 422);

        if ($request->boolean('remote_action')) {
            $type = 'remote-action';
            $payload = [
                'action'   => 'click',
                'x'        => (float) $request->x,
                'y'        => (float) $request->y,
                'selector' => $request->selector,
                'user_id'  => $me,
            ];
        } elseif ($request->boolean('guide_click')) {
            $type = 'guide-click';
            $payload = ['x' => (float) $request->x, 'y' => (float) $request->y];
        } else {
            $type = 'cursor';
            $payload = [
                'x'       => (float) $request->x,
                'y'       => (float) $request->y,
                'name'    => auth()->user()->name,
                'user_id' => $me,
            ];
        }

        broadcast(new CollabEvent("collab-session.{$sessionKey}", $type, $payload));

        return response()->json(['ok' => true]);
    }

    // 스크롤 위치 동기화 (host → participant)
    public function scroll(Request $request, string $sessionKey)
    {
        $request->validate([
            'scroll_x' => 'required|integer|min:0',
            'scroll_y' => 'required|integer|min:0',
        ]);

        $session = CollabSession::where('session_key', $sessionKey)
            ->where('initiator_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if (!$session) return response()->json(['ok' => false], 422);

        broadcast(new CollabEvent(
            "collab-session.{$sessionKey}",
            'scroll',
            [
                'scroll_x' => (int) $request->scroll_x,
                'scroll_y' => (int) $request->scroll_y,
            ]
        ));

        return response()->json(['ok' => true]);
    }

    // 세션 종료 (active) 또는 요청 취소 (pending)
    public function end(string $sessionKey)
    {
        $me = auth()->id();

        $session = CollabSession::where('session_key', $sessionKey)
            ->where(function ($q) use ($me) {
                $q->where('initiator_id', $me)->orWhere('participant_id', $me);
            })
            ->whereIn('status', ['pending', 'active'])
            ->firstOrFail();

        $wasActive = $session->status === 'active';
        $session->update(['status' => 'ended']);

        if ($wasActive) {
            // 활성 세션 종료: 세션 채널로 브로드캐스트
            broadcast(new CollabEvent(
                "collab-session.{$sessionKey}",
                'ended',
                []
            ));
        } else {
            // 대기 중 요청 취소: 상대방 개인 채널로 취소 알림
            $otherId = $session->initiator_id === $me
                ? $session->participant_id
                : $session->initiator_id;

            broadcast(new CollabEvent(
                "collab-user.{$otherId}",
                'declined',
                ['session_key' => $sessionKey]
            ));
        }

        return response()->json(['ok' => true]);
    }

    // 화면 공유 요청 — 상대방 개인 채널로 전달 (세션 채널 auth 타이밍 이슈 방지)
    public function screenRequest(string $sessionKey)
    {
        $me = auth()->user();

        $session = CollabSession::where('session_key', $sessionKey)
            ->where(function ($q) use ($me) {
                $q->where('initiator_id', $me->id)->orWhere('participant_id', $me->id);
            })
            ->where('status', 'active')
            ->firstOrFail();

        $otherId = $session->initiator_id === $me->id
            ? $session->participant_id
            : $session->initiator_id;

        broadcast(new CollabEvent(
            "collab-user.{$otherId}",
            'screen-request',
            ['from_id' => $me->id, 'from_name' => $me->name]
        ));

        return response()->json(['ok' => true]);
    }

    // WebRTC 시그널 중계 (offer / answer / ICE) — 상대방 개인 채널로 전달
    public function screenSignal(Request $request, string $sessionKey)
    {
        $request->validate([
            'signal_type' => 'required|in:offer,answer,ice,sharer-ready',
            'data'        => 'present',
        ]);

        $me = auth()->id();

        $session = CollabSession::where('session_key', $sessionKey)
            ->where(function ($q) use ($me) {
                $q->where('initiator_id', $me)->orWhere('participant_id', $me);
            })
            ->where('status', 'active')
            ->firstOrFail();

        $otherId = $session->initiator_id === $me
            ? $session->participant_id
            : $session->initiator_id;

        broadcast(new CollabEvent(
            "collab-user.{$otherId}",
            'screen-signal',
            ['signal_type' => $request->signal_type, 'data' => $request->data, 'from_id' => $me]
        ));

        return response()->json(['ok' => true]);
    }

    // 화면 공유 종료 — 상대방 개인 채널로 전달
    public function screenEnd(string $sessionKey)
    {
        $me = auth()->id();

        $session = CollabSession::where('session_key', $sessionKey)
            ->where(function ($q) use ($me) {
                $q->where('initiator_id', $me)->orWhere('participant_id', $me);
            })
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if (!$session) return response()->json(['ok' => false], 422);

        $otherId = $session->initiator_id === $me
            ? $session->participant_id
            : $session->initiator_id;

        broadcast(new CollabEvent(
            "collab-user.{$otherId}",
            'screen-ended',
            ['from_id' => $me]
        ));

        return response()->json(['ok' => true]);
    }

    // APP_URL 기준 상대경로 → 현재 환경 절대 URL
    // JS에서 base path를 제거한 상대경로(/messages?x=1)를 전송하므로
    // 여기서는 APP_URL만 앞에 붙이면 됨
    private function toAbsoluteUrl(string $stored): string
    {
        if (str_starts_with($stored, 'http')) {
            // 구버전 절대 URL 호환: path+query만 추출
            $path  = parse_url($stored, PHP_URL_PATH)  ?? '/';
            $query = parse_url($stored, PHP_URL_QUERY);
        } else {
            [$path, $query] = array_pad(explode('?', $stored, 2), 2, null);
        }
        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/') . ($query ? '?' . $query : '');
    }

    // 현재 세션 조회
    public function getCurrentSession()
    {
        $me = auth()->id();

        $session = CollabSession::where(function ($q) use ($me) {
            $q->where('initiator_id', $me)->orWhere('participant_id', $me);
        })->where('status', 'active')
          ->with(['initiator:id,name', 'participant:id,name'])
          ->latest()
          ->first();

        if (!$session) {
            return response()->json(['session' => null]);
        }

        return response()->json([
            'session' => [
                'session_key'      => $session->session_key,
                'initiator_id'     => $session->initiator_id,
                'initiator_name'   => $session->initiator->name,
                'participant_id'   => $session->participant_id,
                'participant_name' => $session->participant->name,
                'permission'       => $session->permission,
                'current_url'      => $this->toAbsoluteUrl($session->current_url),
                'role'             => $session->initiator_id === $me ? 'host' : 'participant',
            ],
        ]);
    }
}
