{{-- 실시간 협업 위젯 --}}
<div id="collab-widget" style="position:relative;">

    {{-- 플로팅 버튼 --}}
    <button id="collab-fab" onclick="collabTogglePanel()" title="{{ __('collab.collab_title') }}"
        style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:none;background:transparent;cursor:pointer;color:#a1a1aa;transition:background .12s,color .12s;position:relative;"
        onmouseover="this.style.background='var(--t50)';this.style.color='var(--tText)'"
        onmouseout="this.style.background='transparent';this.style.color='#a1a1aa'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span id="collab-online-badge" style="display:none;position:absolute;top:2px;right:2px;width:8px;height:8px;border-radius:50%;background:#22c55e;border:1.5px solid #fff;"></span>
        <span id="collab-active-badge" style="display:none;position:absolute;top:-3px;right:-3px;width:10px;height:10px;border-radius:50%;background:#ef4444;border:2px solid #fff;animation:collab-pulse 1.5s infinite;"></span>
    </button>

    {{-- 드롭다운 패널 --}}
    <div id="collab-panel" style="display:none;position:absolute;top:40px;right:0;width:300px;background:#fff;border:1px solid #ede8ff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:9998;overflow:hidden;">

        {{-- 패널 헤더 --}}
        <div style="padding:14px 16px 10px;border-bottom:1px solid #f3f0ff;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:13px;font-weight:600;color:#18181b;">{{ __('collab.collab_title') }}</span>
            <div style="display:flex;align-items:center;gap:4px;">
                <span id="collab-online-count" style="font-size:11px;color:#a1a1aa;"></span>
                <button onclick="collabTogglePanel()" style="width:22px;height:22px;border:none;background:none;cursor:pointer;color:#a1a1aa;display:flex;align-items:center;justify-content:center;border-radius:5px;"
                    onmouseover="this.style.background='#f3f0ff'" onmouseout="this.style.background='none'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- 패널 본문 (상태별 뷰) --}}
        <div id="collab-body" style="max-height:380px;overflow-y:auto;">

            {{-- 뷰: 유저 목록 (idle) --}}
            <div id="collab-view-idle" style="padding:12px 16px;">
                <div style="font-size:11px;font-weight:600;color:#a1a1aa;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">{{ __('collab.online_users') }}</div>
                <div id="collab-user-list">
                    <div style="font-size:12px;color:#a1a1aa;text-align:center;padding:16px 0;">{{ __('collab.no_users') }}</div>
                </div>
            </div>

            {{-- 뷰: 요청 보냄 (pending-out) --}}
            <div id="collab-view-pending-out" style="display:none;padding:20px 16px;text-align:center;">
                <div style="width:44px;height:44px;margin:0 auto 12px;border-radius:50%;background:#f0e9ff;display:flex;align-items:center;justify-content:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div id="collab-pending-out-name" style="font-size:13px;font-weight:600;color:#18181b;margin-bottom:4px;"></div>
                <div style="font-size:12px;color:#a1a1aa;margin-bottom:14px;">{{ __('collab.request_sent_waiting') }}</div>
                <button onclick="collabCancelRequest()" style="padding:6px 16px;border-radius:8px;border:1px solid #ede8ff;background:#fff;font-size:12px;cursor:pointer;color:#71717a;">{{ __('collab.cancel_request') }}</button>
            </div>

            {{-- 뷰: 수신 요청 (pending-in) --}}
            <div id="collab-view-pending-in" style="display:none;padding:20px 16px;text-align:center;">
                <div style="width:44px;height:44px;margin:0 auto 12px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div id="collab-pending-in-name" style="font-size:13px;font-weight:600;color:#18181b;margin-bottom:4px;"></div>
                <div style="font-size:12px;color:#a1a1aa;margin-bottom:14px;">{{ __('collab.request_received') }}</div>
                <div style="display:flex;gap:8px;justify-content:center;">
                    <button onclick="collabAccept()" style="padding:6px 16px;border-radius:8px;border:none;background:#8b5cf6;color:#fff;font-size:12px;cursor:pointer;font-weight:600;">{{ __('collab.accept') }}</button>
                    <button onclick="collabDecline()" style="padding:6px 16px;border-radius:8px;border:1px solid #ede8ff;background:#fff;font-size:12px;cursor:pointer;color:#71717a;">{{ __('collab.decline') }}</button>
                </div>
            </div>

            {{-- 뷰: 활성 세션 (active) --}}
            <div id="collab-view-active" style="display:none;padding:12px 16px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;"></div>
                    <span id="collab-active-partner" style="font-size:13px;font-weight:600;color:#18181b;flex:1;"></span>
                    <span id="collab-active-role-badge" style="font-size:10px;padding:2px 7px;border-radius:99px;background:#f0e9ff;color:#8b5cf6;font-weight:600;"></span>
                </div>

                {{-- 화면 공유 섹션 --}}
                <div style="border-top:1px solid #f3f0ff;padding-top:10px;margin-bottom:10px;">
                    {{-- idle: 요청 버튼 --}}
                    <div id="collab-screen-idle">
                        <button onclick="collabRequestScreenShare()"
                            style="width:100%;padding:7px;border-radius:8px;border:1px solid #ddd6fe;background:#faf5ff;color:#7c3aed;font-size:12px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                            {{ __('collab.screen_share_btn') }}
                        </button>
                    </div>
                    {{-- requesting: 수락 대기 중 --}}
                    <div id="collab-screen-requesting" style="display:none;text-align:center;padding:4px 0;">
                        <div style="font-size:11px;color:#a1a1aa;margin-bottom:6px;">{{ __('collab.screen_requesting') }}</div>
                        <button onclick="collabCancelScreenShare()"
                            style="padding:5px 14px;border-radius:7px;border:1px solid #ede8ff;background:#fff;font-size:11px;cursor:pointer;color:#71717a;">
                            {{ __('collab.screen_share_cancel') }}
                        </button>
                    </div>
                    {{-- sharing: 공유 진행 중 (B 측) --}}
                    <div id="collab-screen-sharing" style="display:none;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#ef4444;flex-shrink:0;animation:collab-pulse 1.5s infinite;display:inline-block;"></span>
                            <span style="font-size:12px;font-weight:600;color:#18181b;">{{ __('collab.screen_sharing_active') }}</span>
                        </div>
                        <button onclick="collabEndScreenShare()"
                            style="width:100%;padding:6px;border-radius:8px;border:1px solid #fee2e2;background:#fff;color:#ef4444;font-size:12px;cursor:pointer;font-weight:600;">
                            {{ __('collab.screen_share_end') }}
                        </button>
                    </div>
                </div>

                <button onclick="collabEnd()" style="width:100%;padding:7px;border-radius:8px;border:1px solid #fee2e2;background:#fff;color:#ef4444;font-size:12px;cursor:pointer;font-weight:600;">{{ __('collab.end_session') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- 화면 공유 수신 알림 토스트 (body로 이동됨) --}}
<div id="collab-screen-notify" style="display:none;position:fixed;bottom:28px;right:28px;width:284px;background:#fff;border:1px solid #ede8ff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.18);z-index:10001;padding:14px 14px 12px;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
        <div style="width:32px;height:32px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </div>
        <div style="flex:1;min-width:0;">
            <div id="collab-screen-notify-name" style="font-size:12px;font-weight:700;color:#18181b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
            <div style="font-size:11px;color:#71717a;margin-top:1px;">{{ __('collab.screen_share_incoming') }}</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button onclick="collabAcceptScreenShare()" style="flex:1;padding:7px;border-radius:8px;border:none;background:#8b5cf6;color:#fff;font-size:12px;cursor:pointer;font-weight:600;">{{ __('collab.accept') }}</button>
        <button onclick="collabDeclineScreenShare()" style="flex:1;padding:7px;border-radius:8px;border:1px solid #ede8ff;background:#fff;font-size:12px;cursor:pointer;color:#71717a;">{{ __('collab.decline') }}</button>
    </div>
</div>

<style>
@keyframes collab-pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.6; transform:scale(1.3); }
}
</style>

<script>
(async function(){
'use strict';

// ── 상태 ──────────────────────────────────────────────────────────────────
// MY_ID는 window.MY_ID가 나중에 설정되므로 함수로 lazy 참조
const myId  = () => window.MY_ID || 0;
const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content || '';
const BASE     = '{{ rtrim(url('collab'), '/') }}';
const _APP_URL = '{{ rtrim(url('/'), '/') }}'; // APP_URL 기준 (localhost: 'http://localhost/supportworks', prod: 'https://www.supportworks.co.kr')

// APP_URL prefix를 제거한 상대경로 반환 → 환경 간 이식 가능
async function _appRelUrl() {
    const href = location.origin + location.pathname + location.search;
    return href.startsWith(_APP_URL) ? (href.slice(_APP_URL.length) || '/') : (location.pathname + location.search);
}

const COLLAB_STR = {
    collab_title:       '{{ __("collab.collab_title") }}',
    online_users:       '{{ __("collab.online_users") }}',
    no_users:           '{{ __("collab.no_users") }}',
    request_sent_waiting: '{{ __("collab.request_sent_waiting") }}',
    cancel_request:     '{{ __("collab.cancel_request") }}',
    request_received:   '{{ __("collab.request_received") }}',
    accept:             '{{ __("collab.accept") }}',
    decline:            '{{ __("collab.decline") }}',
    end_session:        '{{ __("collab.end_session") }}',
    online_prefix:      '{{ __("collab.online_prefix") }}',
    online_suffix:      '{{ __("collab.online_suffix") }}',
    request_arrow:      '{{ __("collab.request_arrow") }}',
    confirm_request:    '{{ __("collab.confirm_request") }}',
    request_fail:       '{{ __("collab.request_fail") }}',
    request_declined:   '{{ __("collab.request_declined") }}',
    role_host:          '{{ __("collab.role_host") }}',
    role_participant:   '{{ __("collab.role_participant") }}',

    screen_share_btn:      '{{ __("collab.screen_share_btn") }}',
    screen_requesting:     '{{ __("collab.screen_requesting") }}',
    screen_sharing_active: '{{ __("collab.screen_sharing_active") }}',
    screen_share_end:      '{{ __("collab.screen_share_end") }}',
    screen_share_confirm:  '{{ __("collab.screen_share_confirm") }}',
    screen_share_declined: '{{ __("collab.screen_share_declined") }}',
    screen_popup_waiting:  '{{ __("collab.screen_popup_waiting") }}',
    screen_popup_blocked:  '{{ __("collab.screen_popup_blocked") }}',
};

let collabState = {
    view:        'idle',   // idle | pending-out | pending-in | active
    sessionKey:  null,
    role:        null,     // host | participant
    partnerId:   null,
    partnerName: null,
    pendingKey:  null,     // inbound pending session key
};

// ── 패널 토글 ─────────────────────────────────────────────────────────────
window.collabTogglePanel = async function() {
    const panel = document.getElementById('collab-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
};

// 패널 외부 클릭 시 닫기
document.addEventListener('click', async function(e) {
    if (!document.getElementById('collab-widget').contains(e.target)) {
        document.getElementById('collab-panel').style.display = 'none';
    }
});

// ── 뷰 전환 ───────────────────────────────────────────────────────────────
async function showView(name) {
    ['idle','pending-out','pending-in','active'].forEach(v => {
        document.getElementById(`collab-view-${v}`).style.display = v === name ? '' : 'none';
    });
    collabState.view = name;

    // 활성 배지
    const activeBadge = document.getElementById('collab-active-badge');
    if (name === 'active') {
        activeBadge.style.display = 'block';
    } else {
        activeBadge.style.display = 'none';
    }
}

// ── Presence 채널 ─────────────────────────────────────────────────────────
const onlineUsers = {};

async function renderUserList() {
    const list  = document.getElementById('collab-user-list');
    const count = document.getElementById('collab-online-count');
    const badge = document.getElementById('collab-online-badge');

    const others = Object.values(onlineUsers).filter(u => u.id !== myId());

    count.textContent = `${COLLAB_STR.online_prefix}${Object.keys(onlineUsers).length}${COLLAB_STR.online_suffix}`;
    badge.style.display = Object.keys(onlineUsers).length > 0 ? 'block' : 'none';

    if (others.length === 0) {
        list.innerHTML = `<div style="font-size:12px;color:#a1a1aa;text-align:center;padding:16px 0;">${COLLAB_STR.no_users}</div>`;
        return;
    }

    list.innerHTML = others.map(u => `
        <div style="display:flex;align-items:center;gap:8px;padding:6px 4px;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''"
            onclick="collabRequestTo(${u.id},'${escHtml(u.name)}')">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                ${escHtml(u.name.charAt(0))}
            </div>
            <span style="font-size:12px;font-weight:500;color:#18181b;flex:1;">${escHtml(u.name)}</span>
            <span style="font-size:10px;color:#a1a1aa;">${COLLAB_STR.request_arrow}</span>
        </div>
    `).join('');
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── 인증/CSRF 만료 처리 ───────────────────────────────────────────────────
let _expiryNotified = false;
async function _handleExpiry(status) {
    if (_expiryNotified) return;
    _expiryNotified = true;
    // 기존 협업 상태 정리
    localStorage.removeItem('collab_session');
    localStorage.removeItem('collab_pending_out');
    // 재로그인/새로고침 안내 배너
    const banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#18181b;color:#fff;text-align:center;padding:10px 16px;font-size:13px;z-index:99999;box-shadow:0 2px 12px rgba(0,0,0,.4);';
    banner.innerHTML = (status === 401
        ? @json(__('collab.session_expired'))
        : @json(__('collab.csrf_expired')))
        + ' &nbsp;<a href="javascript:location.reload()" style="color:#8b5cf6;font-weight:700;text-decoration:none;">{{ __('collab.reload_page') }}</a>';
    document.body.prepend(banner);
}

// JSON 응답 안전 파싱 (401/419 → 만료 처리, HTML 응답 방어)
async function _safeJson(r) {
    if (r.status === 401 || r.status === 419) { _handleExpiry(r.status); return null; }
    if (!r.ok) return null;
    return r.json().catch(() => null);
}

// ── 온라인 사용자 Polling ─────────────────────────────────────────────────
async function loadOnlineUsers() {
    fetch(`${BASE}/online`, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => _safeJson(r))
        .then(d => {
            if (!d) return;
            Object.keys(onlineUsers).forEach(k => delete onlineUsers[k]);
            (d.users || []).forEach(u => { onlineUsers[u.id] = u; });
            renderUserList();
        })
        .catch(() => {});
}

async function sendHeartbeat() {
    fetch(`${BASE}/heartbeat`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => { if (r.status === 401 || r.status === 419) _handleExpiry(r.status); })
    .catch(() => {});
}


// ── 협업 요청 보내기 ─────────────────────────────────────────────────────
window.collabRequestTo = async function(userId, userName) {
    if (collabState.view !== 'idle') return;
    if (!await __confirm(`${userName}${COLLAB_STR.confirm_request}`)) return;

    fetch(`${BASE}/request`, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({ participant_id: userId, current_url: _appRelUrl() }),
    })
    .then(r => _safeJson(r))
    .then(d => {
        if (!d) return;
        if (!d.ok) { alert(d.error || COLLAB_STR.request_fail); return; }
        collabState.sessionKey  = d.session_key;
        collabState.role        = 'host';
        collabState.partnerId   = userId;
        collabState.partnerName = userName;
        document.getElementById('collab-pending-out-name').textContent = userName;
        showView('pending-out');
        localStorage.setItem('collab_pending_out', JSON.stringify({
            sessionKey: d.session_key, role: 'host',
            partnerId: userId, partnerName: userName,
        }));
    })
    .catch(() => {});
};

// ── 요청 취소 ─────────────────────────────────────────────────────────────
window.collabCancelRequest = async function() {
    const key = collabState.sessionKey;
    if (!key) { showView('idle'); return; }
    fetch(`${BASE}/end/${key}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} })
        .finally(() => {
            collabState.sessionKey = null;
            localStorage.removeItem('collab_pending_out');
            showView('idle');
        });
};

// ── 수락 ─────────────────────────────────────────────────────────────────
window.collabAccept = async function() {
    const key = collabState.pendingKey;
    if (!key) return;
    fetch(`${BASE}/respond/${key}`, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({ accept: true }),
    })
    .then(r => {
        // 404: 요청자가 이미 취소한 경우 → idle로 복귀
        if (r.status === 404) { collabState.pendingKey = null; showView('idle'); return null; }
        return _safeJson(r);
    })
    .then(d => {
        if (!d || !d.ok) return;
        collabState.sessionKey = key;
        collabState.role       = 'participant';
        localStorage.setItem('collab_session', JSON.stringify({
            sessionKey: key, role: 'participant',
            partnerId: collabState.partnerId, partnerName: collabState.partnerName,
        }));
        activateSession(key, 'participant', collabState.partnerName);
        subscribeSessionChannel(key);
        if (d.current_url && d.current_url !== location.href) {
            location.href = d.current_url;
        }
    })
    .catch(() => {});
};

// ── 거절 ─────────────────────────────────────────────────────────────────
window.collabDecline = async function() {
    const key = collabState.pendingKey;
    if (!key) return;
    fetch(`${BASE}/respond/${key}`, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({ accept: false }),
    })
    .then(r => { if (r.status === 401 || r.status === 419) _handleExpiry(r.status); })
    .finally(() => {
        collabState.pendingKey = null;
        showView('idle');
    });
};

// ── 개인 이벤트 처리 ─────────────────────────────────────────────────────
async function handlePersonalEvent(e) {
    if (e.type === 'request') {
        collabState.pendingKey  = e.session_key;
        collabState.partnerId   = e.initiator_id;
        collabState.partnerName = e.initiator_name;
        document.getElementById('collab-pending-in-name').textContent = e.initiator_name;
        showView('pending-in');
        document.getElementById('collab-panel').style.display = 'block';
    } else if (e.type === 'accepted') {
        // host: 상대가 수락함
        collabState.sessionKey = e.session_key;
        localStorage.setItem('collab_session', JSON.stringify({
            sessionKey: e.session_key, role: 'host',
            partnerId: collabState.partnerId, partnerName: collabState.partnerName,
        }));
        localStorage.removeItem('collab_pending_out');
        activateSession(e.session_key, 'host', collabState.partnerName);
        subscribeSessionChannel(e.session_key);
        broadcastNavigate(e.session_key);
    } else if (e.type === 'declined') {
        alert(`${collabState.partnerName || ''}${COLLAB_STR.request_declined}`);
        collabState.sessionKey = null;
        localStorage.removeItem('collab_pending_out');
        showView('idle');
    } else if (e.type === 'screen-request' || e.type === 'screen-signal' || e.type === 'screen-ended') {
        handleScreenEvent(e);
    }
}

// ── 세션 활성화 UI ────────────────────────────────────────────────────────
async function activateSession(sessionKey, role, partnerName) {
    collabState.sessionKey  = sessionKey;
    collabState.role        = role;
    collabState.partnerName = partnerName;

    document.getElementById('collab-active-partner').textContent = partnerName;
    document.getElementById('collab-active-role-badge').textContent = role === 'host' ? COLLAB_STR.role_host : COLLAB_STR.role_participant;
    // 승인자(participant)는 화면 공유 요청 버튼 숨김
    document.getElementById('collab-screen-idle').style.display = role === 'host' ? '' : 'none';
    showView('active');
}

// ── 세션 채널 구독 ────────────────────────────────────────────────────────
async function subscribeSessionChannel(key) {
    if (!window.Echo) {
        // Echo가 아직 준비되지 않은 경우 echoReady 후 재시도
        window.addEventListener('echoReady', () => subscribeSessionChannel(key), { once: true });
        return;
    }
    window.Echo.private(`collab-session.${key}`)
        .listen('.CollabEvent', e => handleSessionEvent(e));
}

// ── 커서 공유 ────────────────────────────────────────────────────────────────
(async function _injectCursorStyles() {
    const s = document.createElement('style');
    s.textContent = `
        #collab-partner-cursor{position:fixed;z-index:99999;pointer-events:none;display:none;will-change:transform;}
        #collab-partner-cursor svg{filter:drop-shadow(0 1px 2px rgba(0,0,0,.4));}
        #collab-partner-cursor span{background:#8b5cf6;color:#fff;font-size:10px;font-weight:600;padding:1px 6px;border-radius:4px;white-space:nowrap;vertical-align:top;margin-left:2px;}
        @keyframes collab-ripple{0%{transform:translate(-50%,-50%) scale(0);opacity:.8}100%{transform:translate(-50%,-50%) scale(3);opacity:0}}
    `;
    document.head.appendChild(s);
})();

let _cursorEl = null;
let _cursorLastSend = 0;
let _cursorMoveHandler = null;
let _scrollSendHandler = null;

async function _ensureCursorEl(name) {
    if (!_cursorEl) {
        _cursorEl = document.createElement('div');
        _cursorEl.id = 'collab-partner-cursor';
        _cursorEl.innerHTML =
            `<svg width="18" height="18" viewBox="0 0 20 20" fill="none">` +
            `<path d="M4 2L16 10L10 11L8 18L4 2Z" fill="#8b5cf6" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/>` +
            `</svg><span></span>`;
        document.body.appendChild(_cursorEl);
    }
    _cursorEl.querySelector('span').textContent = name || '';
    return _cursorEl;
}

async function _startCursorTracking(key) {
    // 마우스 이동 → 상대방에게 전송 (20fps 스로틀)
    _cursorMoveHandler = (e) => {
        const now = Date.now();
        if (now - _cursorLastSend < 50) return;
        _cursorLastSend = now;
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        fetch(`${BASE}/cursor/${key}`, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify({x, y}),
        }).catch(()=>{});
    };
    document.addEventListener('mousemove', _cursorMoveHandler);

    // 스크롤 → host만 전송
    if (collabState.role === 'host') {
        let _scrollTimer = null;
        _scrollSendHandler = () => {
            clearTimeout(_scrollTimer);
            _scrollTimer = setTimeout(() => {
                fetch(`${BASE}/scroll/${key}`, {
                    method: 'POST', credentials: 'same-origin',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
                    body: JSON.stringify({scroll_x: window.scrollX, scroll_y: window.scrollY}),
                }).catch(()=>{});
            }, 80);
        };
        window.addEventListener('scroll', _scrollSendHandler, {passive:true});
    }
}

async function _stopCursorTracking() {
    if (_cursorMoveHandler)  { document.removeEventListener('mousemove', _cursorMoveHandler); _cursorMoveHandler = null; }
    if (_scrollSendHandler)  { window.removeEventListener('scroll', _scrollSendHandler); _scrollSendHandler = null; }
    if (_cursorEl)           { _cursorEl.remove(); _cursorEl = null; }
}

async function _showGuideClick(x, y) {
    const r = document.createElement('div');
    r.style.cssText = `position:fixed;z-index:99998;pointer-events:none;` +
        `left:${x*100}%;top:${y*100}%;` +
        `width:28px;height:28px;border-radius:50%;background:rgba(139,92,246,.45);` +
        `animation:collab-ripple .6s ease-out forwards;`;
    document.body.appendChild(r);
    setTimeout(() => r.remove(), 700);
}

async function handleSessionEvent(e) {
    if (e.type === 'navigate') {
        if (collabState.role === 'participant' && e.url && e.url !== location.href) {
            location.href = e.url;
        }
    } else if (e.type === 'ended') {
        onSessionEnded();
    } else if (e.type === 'cursor') {
        const el = _ensureCursorEl(e.name);
        el.style.display = 'block';
        el.style.left = (e.x * window.innerWidth)  + 'px';
        el.style.top  = (e.y * window.innerHeight) + 'px';
    } else if (e.type === 'guide-click') {
        _showGuideClick(e.x, e.y);
    } else if (e.type === 'remote-action') {
        _showGuideClick(e.x, e.y);
        const xPx = e.x * window.innerWidth;
        const yPx = e.y * window.innerHeight;
        const target = document.elementFromPoint(xPx, yPx);
        if (target && target.tagName !== 'HTML' && target.tagName !== 'BODY') {
            target.click();
            if (typeof target.focus === 'function') target.focus();
        }
    } else if (e.type === 'scroll') {
        if (collabState.role === 'participant') {
            window.scrollTo({left: e.scroll_x, top: e.scroll_y, behavior: 'smooth'});
        }
    }
}

// ── URL 브로드캐스트 (host) ────────────────────────────────────────────────
async function broadcastNavigate(key) {
    if (collabState.role !== 'host') return;
    fetch(`${BASE}/navigate/${key}`, {
        method: 'PATCH',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({ url: _appRelUrl() }),
    });
}

// ── 세션 종료 ─────────────────────────────────────────────────────────────
window.collabEnd = async function() {
    const key = collabState.sessionKey;
    if (!key) return;
    fetch(`${BASE}/end/${key}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} })
        .finally(() => onSessionEnded());
};

async function onSessionEnded() {
    localStorage.removeItem('collab_session');
    localStorage.removeItem('collab_pending_out');
    collabState = { view:'idle', sessionKey:null, role:null, partnerId:null, partnerName:null, pendingKey:null };
    document.getElementById('collab-online-badge').style.display = 'none';
    _cleanupScreenShare();
    showView('idle');
}

// ── 페이지 로드 시 세션 복원 ──────────────────────────────────────────────
async function restoreSession() {
    // pending out 복원
    const pendingOut = localStorage.getItem('collab_pending_out');
    if (pendingOut) {
        try {
            const p = JSON.parse(pendingOut);
            collabState.sessionKey  = p.sessionKey;
            collabState.role        = 'host';
            collabState.partnerId   = p.partnerId;
            collabState.partnerName = p.partnerName;
            document.getElementById('collab-pending-out-name').textContent = p.partnerName;
            showView('pending-out');
            return;
        } catch(_) { localStorage.removeItem('collab_pending_out'); }
    }

    // active session 복원
    const stored = localStorage.getItem('collab_session');
    if (!stored) return;
    try {
        const s = JSON.parse(stored);
        // 서버 확인
        fetch(`${BASE}/session`, { headers: { 'Accept': 'application/json' } })
            .then(r => _safeJson(r))
            .then(d => {
                if (!d || !d.session || d.session.session_key !== s.sessionKey) {
                    localStorage.removeItem('collab_session');
                    return;
                }
                const sess = d.session;
                activateSession(sess.session_key, sess.role, sess.role === 'host' ? sess.participant_name : sess.initiator_name);
                subscribeSessionChannel(sess.session_key);
                if (sess.role === 'host') broadcastNavigate(sess.session_key);
            })
            .catch(() => { localStorage.removeItem('collab_session'); });
    } catch(_) { localStorage.removeItem('collab_session'); }
}

// 온라인 목록 폴링은 Echo 없이도 동작하므로 즉시 실행
// 개인 알림 채널(Echo)은 echoReady 이후에 연결
async function _initCollab() {
    // 토스트를 body 직하로 이동 (부모 overflow/transform 영향 차단)
    const screenNotify = document.getElementById('collab-screen-notify');
    if (screenNotify && screenNotify.parentNode !== document.body) document.body.appendChild(screenNotify);

    loadOnlineUsers();
    setInterval(() => { sendHeartbeat(); loadOnlineUsers(); }, 60000);
    restoreSession();
}

// ── 화면 공유 (WebRTC + Screen Capture API + Popup) ──────────────────────

const RTC_CONFIG = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        {
            urls: [
                'turn:{{ config("services.turn.url") }}:80?transport=udp',
                'turn:{{ config("services.turn.url") }}:443?transport=tcp',
                'turns:{{ config("services.turn.url") }}:443',
            ],
            username:   '{{ config("services.turn.username") }}',
            credential: '{{ config("services.turn.credential") }}',
        },
        { urls: 'turn:openrelay.metered.ca:80',  username: 'openrelayproject', credential: 'openrelayproject' },
        { urls: 'turn:openrelay.metered.ca:443', username: 'openrelayproject', credential: 'openrelayproject' },
    ],
};

let screenState = {
    active:        false,
    role:          null,   // 'sharer' | 'viewer'
    pc:            null,   // RTCPeerConnection
    localStream:   null,   // getDisplayMedia stream (sharer)
    pendingIce:    [],     // remoteDescription 설정 전 ICE 버퍼
    popup:         null,   // viewer 팝업 윈도우
    popupChecker:  null,   // setInterval handle
    _offerTimeout: null,   // 30s timeout if offer never arrives
};

async function _showScreenSection(sub) {
    ['idle', 'requesting', 'sharing'].forEach(s => {
        const el = document.getElementById(`collab-screen-${s}`);
        if (el) el.style.display = s === sub ? '' : 'none';
    });
}

// ── A: 화면 공유 요청 + 팝업 선점 오픈 ─────────────────────────────────────
// 팝업은 반드시 click 핸들러 내(동기)에서 열어야 팝업 차단을 피할 수 있음
window.collabRequestScreenShare = async function() {
    const key = collabState.sessionKey;
    if (!key || screenState.active) return;
    if (!await __confirm(`${collabState.partnerName}${COLLAB_STR.screen_share_confirm}`)) return;

    // 클릭 이벤트 동기 컨텍스트에서 팝업 열기 (차단 방지)
    const popup = window.open('', 'collab-screen-share',
        'width=1280,height=740,resizable=yes,menubar=no,toolbar=no,location=no,status=no,scrollbars=no');
    if (!popup) {
        alert(COLLAB_STR.screen_popup_blocked);
        return;
    }
    _renderPopupWaiting(popup, collabState.partnerName);
    screenState.popup = popup;

    // 팝업 닫힘 감지 → 요청 취소
    screenState.popupChecker = setInterval(() => {
        if (popup.closed) {
            clearInterval(screenState.popupChecker);
            screenState.popupChecker = null;
            if (!screenState.active) collabCancelScreenShare();
        }
    }, 800);

    // 30초 내에 offer가 오지 않으면 팝업에 안내
    const offerTimeout = setTimeout(() => {
        if (!screenState.active) _setPopupMsg(@json(__('collab.screen_no_signal')));
    }, 30000);
    // _cleanupScreenShare 시 타임아웃 해제 (closure)
    const _origCleanup = screenState._offerTimeout;
    screenState._offerTimeout = offerTimeout;

    fetch(`${BASE}/screen/request/${key}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { popup.close(); _cleanupScreenShare(); }
        else       { _showScreenSection('requesting'); }
    })
    .catch(() => { popup.close(); _cleanupScreenShare(); });
};

// ── A: 요청 취소 ─────────────────────────────────────────────────────────
window.collabCancelScreenShare = async function() {
    const key = collabState.sessionKey;
    if (key) fetch(`${BASE}/screen/end/${key}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} }).catch(()=>{});
    _cleanupScreenShare();
};

// ── B: 수락 → getDisplayMedia + sharer-ready 신호 전송 (user gesture here!) ─
window.collabAcceptScreenShare = async function() {
    document.getElementById('collab-screen-notify').style.display = 'none';
    const key = collabState.sessionKey;
    if (!key) return;

    // getDisplayMedia는 반드시 user gesture 안에서 동기적으로 호출해야 함
    let stream;
    try {
        stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
        console.log('[Screen] got display stream, tracks:', stream.getTracks().length);
    } catch (err) {
        console.warn('[Screen] getDisplayMedia cancelled or failed:', err);
        fetch(`${BASE}/screen/end/${key}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} }).catch(()=>{});
        return;
    }

    screenState.localStream = stream;
    screenState.role         = 'sharer';
    screenState.active       = true;
    screenState.pendingIce   = [];

    const pc = new RTCPeerConnection(RTC_CONFIG);
    screenState.pc = pc;

    stream.getTracks().forEach(t => pc.addTrack(t, stream));
    console.log('[Screen] tracks added to PC, sending sharer-ready');

    stream.getVideoTracks()[0].addEventListener('ended', () => collabEndScreenShare());

    pc.onicecandidate = (ev) => {
        if (ev.candidate) {
            console.log('[Screen] sharer ICE candidate:', ev.candidate.type);
            _sendScreenSignal(key, 'ice', ev.candidate.toJSON());
        } else {
            console.log('[Screen] sharer ICE gathering complete');
        }
    };

    pc.onconnectionstatechange = () => {
        console.log('[Screen] sharer connectionState:', pc.connectionState);
    };

    // viewer에게 준비 완료를 알림 → viewer가 recvonly offer를 만들어 보냄
    _sendScreenSignal(key, 'sharer-ready', {});
    _startCursorTracking(key);
    _showScreenSection('sharing');
};

// ── B: 거절 ───────────────────────────────────────────────────────────────
window.collabDeclineScreenShare = async function() {
    document.getElementById('collab-screen-notify').style.display = 'none';
    const key = collabState.sessionKey;
    if (key) fetch(`${BASE}/screen/end/${key}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} }).catch(()=>{});
};

// ── B: viewer의 recvonly offer 수신 → setRemoteDescription + answer 전송 ───
// (PC와 트랙은 collabAcceptScreenShare에서 이미 준비됨)
async function _startAsSharer(offerData) {
    const key = collabState.sessionKey;
    const pc  = screenState.pc;
    console.log('[Screen] _startAsSharer(offerData) called, key:', key, 'pc:', !!pc, 'offerData type:', offerData && offerData.type);
    if (!key || !pc) { console.warn('[Screen] _startAsSharer: no key or pc, aborting'); return; }

    try {
        await _setRemoteDescSafe(pc, offerData);
        console.log('[Screen] sharer set remote description (offer) OK, draining', screenState.pendingIce.length, 'buffered ICE');

        for (const ice of screenState.pendingIce) {
            await pc.addIceCandidate(new RTCIceCandidate(ice)).catch(err => console.warn('[Screen] buffered ICE error:', err));
        }
        screenState.pendingIce = [];

        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        console.log('[Screen] answer created and set, sending to viewer');
        _sendScreenSignal(key, 'answer', { type: answer.type, sdp: answer.sdp });
    } catch (err) {
        console.error('[Screen] _startAsSharer error:', err);
    }
}

// ── A: sharer-ready 수신 → recvonly offer 생성 + 전송 ────────────────────
// Viewer가 offer를 만들면 Chrome은 자신이 만든 offer context에서 VP8 rtpmap을
// answer로 수신하므로 거부하지 않음 (Chrome 120+ strict SDP 파서 우회)
async function _startAsViewer() {
    const key = collabState.sessionKey;
    console.log('[Screen] _startAsViewer called, key:', key);
    if (!key) { console.warn('[Screen] _startAsViewer: no sessionKey, aborting'); return; }

    if (screenState._offerTimeout) { clearTimeout(screenState._offerTimeout); screenState._offerTimeout = null; }
    _setPopupMsg(@json(__('collab.webrtc_sharer_ready')));
    screenState.role       = 'viewer';
    screenState.active     = true;
    screenState.pendingIce = [];

    const pc = new RTCPeerConnection(RTC_CONFIG);
    screenState.pc = pc;

    // 영상을 받기만 하므로 recvonly transceiver 추가
    pc.addTransceiver('video', { direction: 'recvonly' });

    pc.ontrack = (ev) => {
        console.log('[Screen] ontrack fired, streams:', ev.streams.length);
        const stream = ev.streams[0] || ev.track && new MediaStream([ev.track]);
        if (!stream) return;
        const popup = screenState.popup;
        if (!popup || popup.closed) { console.warn('[Screen] ontrack: popup closed'); return; }

        try {
            const vid = popup.document.getElementById('collab-screen-video');
            console.log('[Screen] video element:', vid);
            if (vid) {
                vid.muted     = true;
                vid.srcObject = stream;
                vid.style.display = 'block';
                vid.play().catch(err => console.error('[Screen] video.play() failed:', err));
            }
            const loading = popup.document.getElementById('collab-popup-loading');
            if (loading) loading.style.display = 'none';
            const dot = popup.document.getElementById('toolbar-dot');
            if (dot) { dot.style.background = '#22c55e'; dot.classList.remove('waiting'); }
            const title = popup.document.getElementById('collab-popup-title');
            if (title) title.textContent = collabState.partnerName;
        } catch (err) { console.error('[Screen] ontrack DOM error:', err); }
    };

    pc.onicecandidate = (ev) => {
        if (ev.candidate) {
            console.log('[Screen] viewer ICE candidate:', ev.candidate.type);
            _sendScreenSignal(key, 'ice', ev.candidate.toJSON());
        } else {
            console.log('[Screen] viewer ICE gathering complete');
        }
    };

    pc.onconnectionstatechange = () => {
        console.log('[Screen] viewer connectionState:', pc.connectionState);
        const s = pc.connectionState;
        if (s === 'connecting')                          _setPopupMsg(@json(__('collab.webrtc_ice_connecting')));
        else if (s === 'connected')                      _setPopupMsg(@json(__('collab.webrtc_connected')));
        else if (s === 'failed' || s === 'disconnected') {
            _setPopupMsg(@json(__('collab.request_fail')) + ' (' + s + ')' + @json(__('collab.webrtc_failed')));
            try {
                const popup = screenState.popup;
                if (popup && !popup.closed) {
                    const msg = popup.document.getElementById('collab-popup-loading');
                    if (msg) msg.style.display = 'flex';
                }
            } catch (_) {}
        }
    };

    try {
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        console.log('[Screen] viewer offer created and set, sending to sharer');
        _setPopupMsg(@json(__('collab.webrtc_offer_sent')));
        _sendScreenSignal(key, 'offer', { type: offer.type, sdp: offer.sdp });
    } catch (err) {
        console.error('[Screen] _startAsViewer offer error:', err);
        _setPopupMsg(@json(__('collab.webrtc_error')) + err.message);
        return;
    }

    _startCursorTracking(key);
    _showScreenSection('sharing');
}

// ── 팝업 초기 렌더링 (수락 대기 로딩 상태) ────────────────────────────────
async function _renderPopupWaiting(popup, partnerName) {
    const safe = String(partnerName).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    popup.document.open();
    popup.document.write(`<!DOCTYPE html><html lang="ko"><head>
<meta charset="UTF-8">
<title>${safe}{{ __('collab.popup_title_suffix') }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#09090b;color:#fff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column;height:100vh;overflow:hidden}
#toolbar{background:#18181b;border-bottom:1px solid #27272a;padding:8px 16px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:8px}
#toolbar .left{display:flex;align-items:center;gap:8px;min-width:0;flex:1}
.dot{width:8px;height:8px;border-radius:50%;background:#ef4444;flex-shrink:0;animation:pulse 1.5s infinite}
.dot.waiting{background:#f59e0b}
#collab-popup-title{font-size:13px;color:#d4d4d8;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#ctrl-btn{padding:5px 11px;border-radius:7px;border:1px solid #3f3f46;background:#27272a;color:#a1a1aa;font-size:11px;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:5px;flex-shrink:0;transition:background .12s,color .12s,border-color .12s}
#ctrl-btn.on{background:#7c3aed;border-color:#7c3aed;color:#fff}
#ctrl-btn.on:hover{background:#6d28d9}
#ctrl-btn:not(.on):hover{background:#3f3f46;color:#d4d4d8}
#end-btn{background:#ef4444;color:#fff;border:none;padding:6px 14px;border-radius:7px;cursor:pointer;font-size:12px;font-weight:600;flex-shrink:0}
#end-btn:hover{background:#dc2626}
#video-wrap{flex:1;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
#collab-screen-video{width:100%;height:100%;object-fit:contain;display:none}
#ctrl-overlay{position:absolute;inset:0;z-index:10;display:none;cursor:crosshair}
#ctrl-cursor{position:fixed;z-index:99999;pointer-events:none;width:18px;height:18px;border:2px solid #a78bfa;border-radius:50%;transform:translate(-50%,-50%);transition:left .04s,top .04s;display:none}
#collab-popup-loading{position:absolute;display:flex;flex-direction:column;align-items:center;gap:14px}
.spinner{width:38px;height:38px;border:3px solid #3f3f46;border-top-color:#8b5cf6;border-radius:50%;animation:spin .8s linear infinite}
.msg{font-size:13px;color:#71717a}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}
@keyframes spin{to{transform:rotate(360deg)}}
</style></head><body>
<div id="toolbar">
  <div class="left">
    <span class="dot waiting" id="toolbar-dot"></span>
    <span id="collab-popup-title">${safe} — ${COLLAB_STR.screen_popup_waiting}</span>
  </div>
  <button id="ctrl-btn"
    onclick="window.opener&&window.opener._popupToggleControl&&window.opener._popupToggleControl()"
    title="{{ __('collab.remote_control_toggle') }}">
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
    {{ __('collab.remote_control') }}
  </button>
  <button id="end-btn" onclick="window.opener&&window.opener.collabEndScreenShare?window.opener.collabEndScreenShare():window.close()">{{ __('common.close') }}</button>
</div>
<div id="video-wrap">
  <div id="collab-popup-loading">
    <div class="spinner"></div>
    <div class="msg">${COLLAB_STR.screen_popup_waiting}</div>
  </div>
  <video id="collab-screen-video" autoplay playsinline></video>
  <div id="ctrl-overlay"
    onclick="window.opener&&window.opener._popupClick&&window.opener._popupClick(event.clientX,event.clientY)"
    onmousemove="window.opener&&window.opener._popupMove&&window.opener._popupMove(event.clientX,event.clientY)"
    onmouseleave="window.opener&&window.opener._popupLeave&&window.opener._popupLeave()"></div>
</div>
<div id="ctrl-cursor"></div>
</body></html>`);
    popup.document.close();
}

// ── SDP 전처리: VP8 전용 비디오 + Chrome strict parser 거부 패턴 일괄 제거
async function _presanitizeSdp(sdp) {
    const lines = sdp.split(/\r?\n/);

    // 1단계: 비디오 섹션에서 VP8 PT 수집
    const vp8Pts = new Set();
    let inVid = false;
    for (const ln of lines) {
        if (ln.startsWith('m=')) inVid = ln.startsWith('m=video');
        if (inVid) {
            const m = ln.match(/^a=rtpmap:(\d+) VP8\//i);
            if (m) vp8Pts.add(m[1]);
        }
    }

    // 2단계: 필터링
    inVid = false;
    const out = [];
    for (const ln of lines) {
        if (ln === '') continue;                              // 빈 줄 제거
        if (ln.startsWith('m=')) inVid = ln.startsWith('m=video');

        // 모든 섹션: ssrc 관련 전부 제거
        if (/^a=ssrc[:-]/.test(ln)) continue;
        // 모든 섹션: rtcp-fb 전부 제거 (Chrome 120+에서 다양한 variant 거부)
        if (ln.startsWith('a=rtcp-fb:')) continue;

        if (inVid && vp8Pts.size) {
            // m=video 줄: VP8 PT만 유지
            if (ln.startsWith('m=video')) {
                const p = ln.split(' ');
                const kept = p.slice(3).filter(pt => vp8Pts.has(pt));
                out.push(kept.length ? [...p.slice(0, 3), ...kept].join(' ') : ln);
                continue;
            }
            // a=rtpmap / a=fmtp: VP8 외 PT 제거
            const ptM = ln.match(/^a=(?:rtpmap|fmtp):(\d+)/);
            if (ptM && !vp8Pts.has(ptM[1])) continue;
        }

        out.push(ln);
    }
    return out.join('\r\n') + '\r\n';
}

// ── SDP 자가복구: 전처리 후에도 남은 거부 라인을 에러에서 추출해 제거 후 재시도
async function _setRemoteDescSafe(pc, sdpObj) {
    const rawSdp = sdpObj.sdp || '';
    // CRLF 정규화 → 전처리
    let sdp = rawSdp.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\n/g, '\r\n');
    sdp = _presanitizeSdp(sdp);

    let lastErr = null;
    for (let i = 0; i < 60; i++) {
        try {
            await pc.setRemoteDescription(new RTCSessionDescription({ type: sdpObj.type, sdp }));
            if (i > 0) console.log('[Screen] setRemoteDescription OK after', i, 'retry(s)');
            return;
        } catch (err) {
            lastErr = err;
            const msg = err.message || '';
            const m = msg.match(/parse SessionDescription\.\s*(.+?)\s*(?:Invalid SDP line|Expects m line)/i);
            if (!m) throw err;
            const bad = m[1].trim();
            if (bad.startsWith('m=')) throw err; // 구조적 오류 — 제거 불가
            console.warn('[Screen] removing SDP line (' + (i+1) + '):', JSON.stringify(bad));
            const before = sdp;
            sdp = sdp.split(/\r?\n/).filter(l => l.trim() !== bad && l !== '').join('\r\n') + '\r\n';
            if (sdp === before) throw err;
        }
    }
    throw lastErr || new Error('SDP still invalid after 60 retries');
}

// ── 원격 조작 클릭 전송 (팝업 → 셰어러) ──────────────────────────────────
window._sendRemoteAction = async function(x, y) {
    const key = collabState.sessionKey;
    if (!key) return;
    fetch(`${BASE}/cursor/${key}`, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ x, y, remote_action: true }),
    }).catch(() => {});
};

// ── 팝업 원격 조작 UI 핸들러 (window.opener 경유) ─────────────────────────
window._popupToggleControl = async function() {
    const popup = screenState.popup;
    if (!popup || popup.closed) return;
    const btn     = popup.document.getElementById('ctrl-btn');
    const overlay = popup.document.getElementById('ctrl-overlay');
    if (!btn || !overlay) return;
    const on = btn.classList.toggle('on');
    overlay.style.display = on ? 'block' : 'none';
};

window._popupClick = async function(clientX, clientY) {
    const popup = screenState.popup;
    if (!popup || popup.closed) return;
    const vid = popup.document.getElementById('collab-screen-video');
    if (!vid || vid.style.display === 'none') return;
    const rect = vid.getBoundingClientRect();
    const vw = vid.videoWidth || vid.clientWidth;
    const vh = vid.videoHeight || vid.clientHeight;
    const ew = vid.clientWidth, eh = vid.clientHeight;
    const scale = Math.min(ew / vw, eh / vh);
    const cw = vw * scale, ch = vh * scale;
    const crX = (ew - cw) / 2, crY = (eh - ch) / 2;
    const rx = Math.max(0, Math.min(1, (clientX - rect.left - crX) / cw));
    const ry = Math.max(0, Math.min(1, (clientY - rect.top  - crY) / ch));
    window._sendRemoteAction(rx, ry);
    const c = popup.document.getElementById('ctrl-cursor');
    if (c) {
        c.style.left = clientX + 'px';
        c.style.top  = clientY + 'px';
        c.style.display = 'block';
        c.style.background = 'rgba(167,139,250,.35)';
        setTimeout(() => { if (c) c.style.background = 'transparent'; }, 300);
    }
};

window._popupMove = async function(clientX, clientY) {
    const popup = screenState.popup;
    if (!popup || popup.closed) return;
    const c = popup.document.getElementById('ctrl-cursor');
    if (!c) return;
    c.style.left = clientX + 'px';
    c.style.top  = clientY + 'px';
    c.style.display = 'block';
};

window._popupLeave = async function() {
    const popup = screenState.popup;
    if (!popup || popup.closed) return;
    const c = popup.document.getElementById('ctrl-cursor');
    if (c) c.style.display = 'none';
};

// ── 팝업 상태 메시지 업데이트 헬퍼 ────────────────────────────────────────
async function _setPopupMsg(text) {
    try {
        const popup = screenState.popup;
        if (!popup || popup.closed) return;
        const el = popup.document.querySelector('#collab-popup-loading .msg');
        if (el) el.textContent = text;
    } catch(_) {}
}

// ── 시그널 전송 ────────────────────────────────────────────────────────────
async function _sendScreenSignal(sessionKey, signalType, data) {
    fetch(`${BASE}/screen/signal/${sessionKey}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ signal_type: signalType, data }),
    }).catch(() => {});
}

// ── Pusher 개인 채널에서 수신한 화면 공유 이벤트 처리 ──────────────────────
async function handleScreenEvent(e) {
    console.log('[Screen] handleScreenEvent:', e.type, 'signal_type:', e.signal_type, 'from:', e.from_id, 'me:', myId());
    if (e.from_id === myId()) { console.log('[Screen] ignoring own event'); return; }

    if (e.type === 'screen-request') {
        document.getElementById('collab-screen-notify-name').textContent = e.from_name;
        document.getElementById('collab-screen-notify').style.display = 'block';

    } else if (e.type === 'screen-signal') {
        const { signal_type, data } = e;

        if (signal_type === 'sharer-ready') {
            // sharer가 getDisplayMedia + PC 준비 완료 → viewer가 recvonly offer 생성
            console.log('[Screen] received sharer-ready, viewer creating recvonly offer');
            _startAsViewer();

        } else if (signal_type === 'offer') {
            // viewer의 recvonly offer 수신 → sharer가 answer 생성
            console.log('[Screen] received offer from viewer, sharer creating answer');
            _startAsSharer(data);

        } else if (signal_type === 'answer') {
            // sharer의 answer 수신 → viewer가 setRemoteDescription
            // Chrome은 자신이 만든 offer context에서 answer를 받으므로 VP8 rtpmap 거부 없음
            console.log('[Screen] received answer, setting remote description on viewer');
            if (screenState.pc) {
                _setRemoteDescSafe(screenState.pc, data)
                    .then(() => {
                        console.log('[Screen] viewer set remote description (answer) OK');
                        const buf = screenState.pendingIce.splice(0);
                        console.log('[Screen] draining', buf.length, 'buffered ICE on viewer');
                        buf.forEach(ice => screenState.pc.addIceCandidate(new RTCIceCandidate(ice)).catch(err => console.warn('[Screen] viewer ICE add error:', err)));
                    }).catch(err => console.error('[Screen] viewer setRemoteDescription(answer) error:', err));
            } else {
                console.warn('[Screen] received answer but no pc on viewer side');
            }

        } else if (signal_type === 'ice') {
            const pc = screenState.pc;
            if (pc && pc.remoteDescription) {
                pc.addIceCandidate(new RTCIceCandidate(data)).catch(err => console.warn('[Screen] addIceCandidate error:', err));
            } else {
                console.log('[Screen] buffering ICE candidate (no remoteDescription yet), total buffered:', screenState.pendingIce.length + 1);
                screenState.pendingIce.push(data);
            }
        }

    } else if (e.type === 'screen-ended') {
        _cleanupScreenShare();
    }
}

// ── 화면 공유 종료 ─────────────────────────────────────────────────────────
window.collabEndScreenShare = async function() {
    const key = collabState.sessionKey;
    if (key) fetch(`${BASE}/screen/end/${key}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} }).catch(()=>{});
    _cleanupScreenShare();
};

async function _cleanupScreenShare() {
    _stopCursorTracking();
    if (screenState._offerTimeout) { clearTimeout(screenState._offerTimeout); screenState._offerTimeout = null; }
    if (screenState.popupChecker)  { clearInterval(screenState.popupChecker); screenState.popupChecker = null; }
    if (screenState.localStream)   { screenState.localStream.getTracks().forEach(t => t.stop()); screenState.localStream = null; }
    if (screenState.pc)            { screenState.pc.close(); screenState.pc = null; }
    if (screenState.popup && !screenState.popup.closed) { screenState.popup.close(); }
    screenState.popup      = null;
    screenState.active     = false;
    screenState.role       = null;
    screenState.pendingIce = [];

    const notify = document.getElementById('collab-screen-notify');
    if (notify) notify.style.display = 'none';
    _showScreenSection('idle');
}

async function _initCollabEchoChannel() {
    if (!window.Echo || !myId()) return;
    window.Echo.private(`collab-user.${myId()}`)
        .listen('.CollabEvent', e => handlePersonalEvent(e));
    // restoreSession이 echoReady보다 먼저 완료된 경우 세션 채널을 여기서 구독
    if (collabState.sessionKey) {
        subscribeSessionChannel(collabState.sessionKey);
    }
}

// DOM 준비 직후 폴링 시작 (Echo 불필요)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _initCollab);
} else {
    _initCollab();
}

// Echo 준비 후 개인 채널 구독
if (window.Echo) {
    _initCollabEchoChannel();
} else {
    window.addEventListener('echoReady', _initCollabEchoChannel, { once: true });
}

})();
</script>
