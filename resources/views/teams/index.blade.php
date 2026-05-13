@extends('layouts.app')

@section('title', 'Microsoft Teams')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

{{-- ───── 인증 카드 ───── --}}
<div style="background:#fff;border-radius:14px;border:1px solid var(--t100);box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
    <div style="padding:20px 24px 18px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:12px;">
            {{-- Teams 아이콘 --}}
            <div style="width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#5558af,#7b83eb);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(85,88,175,.3);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M14 5.5C14 6.88 12.88 8 11.5 8S9 6.88 9 5.5 10.12 3 11.5 3 14 4.12 14 5.5z" fill="#fff"/>
                    <path d="M19 7.5C19 8.33 18.33 9 17.5 9S16 8.33 16 7.5 16.67 6 17.5 6 19 6.67 19 7.5z" fill="rgba(255,255,255,.7)"/>
                    <path d="M16 10h3a1 1 0 011 1v4a3 3 0 01-3 3h-.5A5.5 5.5 0 0111 13.5V10h5z" fill="rgba(255,255,255,.85)"/>
                    <path d="M5 10h9v3.5A4.5 4.5 0 019.5 18h-1A3.5 3.5 0 015 14.5V10z" fill="#fff"/>
                </svg>
            </div>
            <div>
                <h2 style="font-size:15px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.teams_title') }}</h2>
                <p style="font-size:12px;color:#9ca3af;margin:0;">{{ __('team.teams_desc') }}</p>
            </div>
        </div>
        {{-- 인증 상태 배지 --}}
        <div id="auth-badge">
            @if($setting->is_verified)
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:5px 12px;background:#dcfce7;color:#15803d;border-radius:20px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#16a34a;animation:pulse 1.8s infinite;display:inline-block;"></span>
                {{ __('team.verified_badge') }}
            </span>
            @else
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:5px 12px;background:#f3f4f6;color:#9ca3af;border-radius:20px;">
                <span style="width:7px;height:7px;border-radius:50%;background:#d1d5db;display:inline-block;"></span>
                {{ __('team.unverified_badge') }}
            </span>
            @endif
        </div>
    </div>

    <div style="padding:22px 24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px;letter-spacing:.3px;">TENANT ID</label>
                <input id="tenant_id" type="text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    value="{{ $setting->tenant_id ?? '' }}"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#1e1b2e;outline:none;transition:border-color .15s;box-sizing:border-box;font-family:monospace;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px;letter-spacing:.3px;">CLIENT ID</label>
                <input id="client_id" type="text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    value="{{ $setting->client_id ?? '' }}"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#1e1b2e;outline:none;transition:border-color .15s;box-sizing:border-box;font-family:monospace;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px;letter-spacing:.3px;">CLIENT SECRET</label>
            <div style="position:relative;">
                <input id="client_secret" type="password"
                    placeholder="{{ $setting->client_secret ? __('team.secret_saved') : __('team.secret_placeholder') }}"
                    style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#1e1b2e;outline:none;transition:border-color .15s;box-sizing:border-box;font-family:monospace;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                <button type="button" onclick="toggleSecret()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:2px;">
                    <svg id="eye-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin:5px 0 0;">{{ __('team.azure_hint') }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <button onclick="verifyAuth()" id="verify-btn"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 20px;background:linear-gradient(135deg,#5558af,#7b83eb);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;"
                onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                {{ __('team.verify_btn') }}
            </button>
            <span id="verify-msg" style="font-size:13px;"></span>
        </div>
    </div>
</div>

{{-- ───── 기능 패널 (인증됨 상태에서만 활성화) ───── --}}
<div id="feature-area" style="{{ $setting->is_verified ? '' : 'opacity:.4;pointer-events:none;filter:blur(1px);' }}transition:all .3s;">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        {{-- ① 채널 메시지 전송 --}}
        <div class="feature-card" style="background:#fff;border-radius:14px;border:1px solid var(--t100);box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#5558af22,#7b83eb22);display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" fill="none" stroke="#5558af" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                </div>
                <h3 style="font-size:13px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.feature_channel_msg') }}</h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
                <select id="msg-team" onchange="loadChannels('msg')"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;background:#fff;cursor:pointer;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                    <option value="">{{ __('team.select_team') }}</option>
                </select>
                <select id="msg-channel"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;background:#fff;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                    <option value="">{{ __('team.select_channel') }}</option>
                </select>
                <textarea id="msg-text" rows="3" placeholder="{{ __('team.message_placeholder') }}"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;resize:vertical;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                <button onclick="sendMessage()" style="align-self:flex-end;padding:8px 18px;background:#5558af;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">{{ __('team.send_btn') }}</button>
                <div id="msg-result" style="font-size:12px;"></div>
            </div>
        </div>

        {{-- ② 채팅 생성 --}}
        <div class="feature-card" style="background:#fff;border-radius:14px;border:1px solid var(--t100);box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#6ee7b722,#10b98122);display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                </div>
                <h3 style="font-size:13px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.feature_create_chat') }}</h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
                <p style="font-size:11px;color:#9ca3af;margin:0;">{{ __('team.chat_member_hint') }}</p>
                <textarea id="chat-members" rows="3" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx&#10;yyyyyyyy-yyyy-yyyy-yyyy-yyyyyyyyyyyy"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;color:#374151;outline:none;resize:none;box-sizing:border-box;font-family:monospace;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                <input id="chat-topic" type="text" placeholder="{{ __('team.chat_topic_placeholder') }}"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                <button onclick="createChat()" style="align-self:flex-end;padding:8px 18px;background:#059669;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">{{ __('team.create_chat_btn') }}</button>
                <div id="chat-result" style="font-size:12px;"></div>
            </div>
        </div>

        {{-- ③ 사용자/조직 조회 --}}
        <div class="feature-card" style="background:#fff;border-radius:14px;border:1px solid var(--t100);box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#fcd34d22,#f59e0b22);display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h3 style="font-size:13px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.feature_user_search') }}</h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;gap:8px;">
                    <input id="user-search" type="text" placeholder="{{ __('team.user_search_placeholder') }}"
                        style="flex:1;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;"
                        onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'"
                        onkeydown="if(event.key==='Enter') searchUsers()">
                    <button onclick="searchUsers()" style="padding:8px 14px;background:#d97706;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;transition:opacity .15s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">{{ __('team.search_btn') }}</button>
                </div>
                <div id="user-results" style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;"></div>
            </div>
        </div>

        {{-- ④ 파일 업로드 (SharePoint) --}}
        <div class="feature-card" style="background:#fff;border-radius:14px;border:1px solid var(--t100);box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--tBg);display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#f9a8d422,#ec489922);display:flex;align-items:center;justify-content:center;">
                    <svg width="16" height="16" fill="none" stroke="#db2777" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>
                <h3 style="font-size:13px;font-weight:700;color:#1e1b2e;margin:0;">{{ __('team.feature_file_upload') }} <span style="font-size:11px;color:#9ca3af;font-weight:400;">— SharePoint</span></h3>
            </div>
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
                <select id="site-select" onchange="loadDrives()"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;background:#fff;cursor:pointer;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                    <option value="">{{ __('team.select_site') }}</option>
                </select>
                <select id="drive-select"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;background:#fff;"
                    onfocus="this.style.borderColor='var(--t300)'" onblur="this.style.borderColor='#e5e7eb'">
                    <option value="">{{ __('team.select_drive') }}</option>
                </select>

                {{-- 드래그앤드롭 업로드 영역 --}}
                <div id="drop-zone"
                    style="border:2px dashed #e5e7eb;border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:all .15s;"
                    ondragover="event.preventDefault();this.style.borderColor='var(--t500)';this.style.background='var(--tBg)';"
                    ondragleave="this.style.borderColor='#e5e7eb';this.style.background='transparent';"
                    ondrop="handleDrop(event)"
                    onclick="document.getElementById('file-input').click()">
                    <svg width="28" height="28" fill="none" stroke="var(--t300)" viewBox="0 0 24 24" style="margin-bottom:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p id="drop-label" style="font-size:12px;color:#9ca3af;margin:0;">{{ __('team.drop_label') }}</p>
                    <input id="file-input" type="file" style="display:none;" onchange="handleFileSelect(this)">
                </div>

                <button onclick="uploadFile()" id="upload-btn"
                    style="padding:8px 18px;background:#db2777;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;align-self:flex-end;"
                    onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">{{ __('team.upload_btn') }}</button>
                <div id="upload-result" style="font-size:12px;"></div>
            </div>
        </div>

    </div>
</div>

</div>
@endsection

@section('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const STR = {
    verifying:          '{{ __("team.verifying") }}',
    verify_btn:         '{{ __("team.verify_btn") }}',
    auth_all_required:  '{{ __("team.auth_all_required") }}',
    network_error:      '{{ __("team.network_error") }}',
    sending_msg:        '{{ __("team.sending_msg") }}',
    msg_all_required:   '{{ __("team.msg_all_required") }}',
    msg_sent:           '{{ __("team.msg_sent") }}',
    creating_chat:      '{{ __("team.creating_chat") }}',
    chat_member_req:    '{{ __("team.chat_member_required") }}',
    chat_created:       '{{ __("team.chat_created") }}',
    chat_open:          '{{ __("team.chat_open") }}',
    searching:          '{{ __("team.searching") }}',
    no_results:         '{{ __("team.no_results") }}',
    copy_id:            '{{ __("team.copy_id") }}',
    select_team:        '{{ __("team.select_team") }}',
    select_channel:     '{{ __("team.select_channel") }}',
    select_site:        '{{ __("team.select_site") }}',
    select_drive:       '{{ __("team.select_drive") }}',
    drop_label:         '{{ __("team.drop_label") }}',
    uploading:          '{{ __("team.uploading") }}',
    site_drive_req:     '{{ __("team.site_drive_required") }}',
    file_required:      '{{ __("team.file_required") }}',
    upload_fail:        '{{ __("team.upload_fail") }}',
    verified_badge:     '{{ __("team.verified_badge") }}',
    unverified_badge:   '{{ __("team.unverified_badge") }}',
    upload_btn:         '{{ __("team.upload_btn") }}',
    auth_verified:      '{{ __("team.auth_verified") }}',
    upload_done:        '{{ __("team.upload_done") }}',
    open:               '{{ __("team.open") }}',
};
let selectedFile = null;

// ─── 시크릿 표시 토글 ──────────────────────────────────────────
function toggleSecret() {
    const inp = document.getElementById('client_secret');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

// ─── 인증 ──────────────────────────────────────────────────────
async function verifyAuth() {
    const btn = document.getElementById('verify-btn');
    const msg = document.getElementById('verify-msg');
    const body = {
        tenant_id:     document.getElementById('tenant_id').value.trim(),
        client_id:     document.getElementById('client_id').value.trim(),
        client_secret: document.getElementById('client_secret').value.trim() || '••••••••••••••••',
        _token:        CSRF,
    };
    if (!body.tenant_id || !body.client_id) { showMsg(msg, STR.auth_all_required, 'red'); return; }
    btn.disabled = true;
    btn.textContent = STR.verifying;
    showMsg(msg, '', '');
    try {
        const res  = await apiFetch('{{ route("teams.verify") }}', 'POST', body);
        const data = await res.json();
        if (data.ok) {
            showMsg(msg, `✓ ${data.org} ${STR.auth_verified}`, '#16a34a');
            setBadge(true);
            enableFeatures();
            loadTeams();
            loadSites();
        } else {
            showMsg(msg, '✗ ' + data.error, '#dc2626');
            setBadge(false);
        }
    } catch(e) { showMsg(msg, STR.network_error, '#dc2626'); }
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> ' + STR.verify_btn;
}

function setBadge(ok) {
    document.getElementById('auth-badge').innerHTML = ok
        ? `<span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:5px 12px;background:#dcfce7;color:#15803d;border-radius:20px;"><span style="width:7px;height:7px;border-radius:50%;background:#16a34a;animation:pulse 1.8s infinite;display:inline-block;"></span>${STR.verified_badge}</span>`
        : `<span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:5px 12px;background:#fef2f2;color:#dc2626;border-radius:20px;"><span style="width:7px;height:7px;border-radius:50%;background:#ef4444;display:inline-block;"></span>${STR.unverified_badge}</span>`;
}

function enableFeatures() {
    const el = document.getElementById('feature-area');
    el.style.opacity = '1';
    el.style.pointerEvents = 'auto';
    el.style.filter = 'none';
}

// ─── Teams 목록 로드 ────────────────────────────────────────────
async function loadTeams() {
    try {
        const data = await apiGet('{{ route("teams.api.teams") }}');
        if (!data.ok) return;
        const sel = document.getElementById('msg-team');
        sel.innerHTML = `<option value="">${STR.select_team}</option>`;
        data.data.forEach(t => {
            sel.innerHTML += `<option value="${t.id}">${t.displayName}</option>`;
        });
    } catch(e) {}
}

async function loadChannels(prefix) {
    const teamId = document.getElementById(`${prefix}-team`).value;
    if (!teamId) return;
    try {
        const data = await apiGet(`{{ url('teams/api/teams') }}/${teamId}/channels`);
        if (!data.ok) return;
        const sel = document.getElementById(`${prefix}-channel`);
        sel.innerHTML = `<option value="">${STR.select_channel}</option>`;
        data.data.forEach(c => {
            sel.innerHTML += `<option value="${c.id}">${c.displayName}</option>`;
        });
    } catch(e) {}
}

// ─── 채널 메시지 전송 ───────────────────────────────────────────
async function sendMessage() {
    const res  = document.getElementById('msg-result');
    const body = {
        team_id:    document.getElementById('msg-team').value,
        channel_id: document.getElementById('msg-channel').value,
        message:    document.getElementById('msg-text').value.trim(),
        _token: CSRF,
    };
    if (!body.team_id || !body.channel_id || !body.message) { showMsg(res, STR.msg_all_required, '#d97706'); return; }
    showMsg(res, STR.sending_msg, '#9ca3af');
    const data = await postJson('{{ route("teams.api.message") }}', body);
    data.ok ? showMsg(res, STR.msg_sent, '#16a34a') : showMsg(res, '✗ ' + data.error, '#dc2626');
    if (data.ok) document.getElementById('msg-text').value = '';
}

// ─── 채팅 생성 ──────────────────────────────────────────────────
async function createChat() {
    const res      = document.getElementById('chat-result');
    const rawIds   = document.getElementById('chat-members').value.trim();
    const memberIds = rawIds.split(/[\n,]+/).map(s => s.trim()).filter(Boolean);
    const topic    = document.getElementById('chat-topic').value.trim();
    if (!memberIds.length) { showMsg(res, STR.chat_member_req, '#d97706'); return; }
    showMsg(res, STR.creating_chat, '#9ca3af');
    const data = await postJson('{{ route("teams.api.chat") }}', { member_ids: memberIds, topic, _token: CSRF });
    if (data.ok) {
        const url = data.chat?.webUrl ?? '';
        showMsg(res, `${STR.chat_created}${url ? ' — <a href="'+url+'" target="_blank" style="color:#5558af;">' + STR.chat_open + '</a>' : ''}`, '#16a34a');
    } else {
        showMsg(res, '✗ ' + data.error, '#dc2626');
    }
}

// ─── 사용자 검색 ────────────────────────────────────────────────
async function searchUsers() {
    const q   = document.getElementById('user-search').value.trim();
    const box = document.getElementById('user-results');
    if (!q || q.length < 2) return;
    box.innerHTML = `<div style="font-size:12px;color:#9ca3af;padding:6px;">${STR.searching}</div>`;
    const data = await apiGet('{{ route("teams.api.users") }}?q=' + encodeURIComponent(q));
    if (!data.ok) { box.innerHTML = `<div style="font-size:12px;color:#dc2626;">${data.error}</div>`; return; }
    if (!data.data.length) { box.innerHTML = `<div style="font-size:12px;color:#9ca3af;">${STR.no_results}</div>`; return; }
    box.innerHTML = data.data.map(u => `
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--tBg);border-radius:8px;border:1px solid var(--t100);">
            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--t300),var(--t500));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">${(u.displayName||'?').charAt(0)}</div>
            <div style="min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${u.displayName||''}</div>
                <div style="font-size:11px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${u.mail||u.userPrincipalName||''}</div>
                ${u.jobTitle ? `<div style="font-size:10px;color:var(--t300);">${u.jobTitle}${u.department ? ' · '+u.department : ''}</div>` : ''}
            </div>
            <div style="margin-left:auto;flex-shrink:0;">
                <button onclick="navigator.clipboard.writeText('${u.id}')" title="${STR.copy_id}"
                    style="padding:3px 8px;background:var(--t100);color:var(--tText);border:none;border-radius:5px;font-size:10px;font-weight:700;cursor:pointer;">${STR.copy_id}</button>
            </div>
        </div>
    `).join('');
}

// ─── SharePoint 사이트 / 드라이브 ──────────────────────────────
async function loadSites() {
    try {
        const data = await apiGet('{{ route("teams.api.sites") }}');
        if (!data.ok) return;
        const sel = document.getElementById('site-select');
        sel.innerHTML = `<option value="">${STR.select_site}</option>`;
        data.data.forEach(s => {
            sel.innerHTML += `<option value="${s.id}">${s.displayName || s.name}</option>`;
        });
    } catch(e) {}
}

async function loadDrives() {
    const siteId = document.getElementById('site-select').value;
    if (!siteId) return;
    const data = await apiGet(`{{ url('teams/api/sites') }}/${encodeURIComponent(siteId)}/drives`);
    if (!data.ok) return;
    const sel = document.getElementById('drive-select');
    sel.innerHTML = `<option value="">${STR.select_drive}</option>`;
    data.data.forEach(d => {
        sel.innerHTML += `<option value="${d.id}">${d.name}</option>`;
    });
}

// ─── 파일 업로드 ────────────────────────────────────────────────
function handleFileSelect(input) {
    if (input.files[0]) {
        selectedFile = input.files[0];
        document.getElementById('drop-label').textContent = selectedFile.name;
        document.getElementById('drop-zone').style.borderColor = 'var(--t500)';
        document.getElementById('drop-zone').style.background  = 'var(--tBg)';
    }
}

function handleDrop(event) {
    event.preventDefault();
    document.getElementById('drop-zone').style.borderColor = '#e5e7eb';
    document.getElementById('drop-zone').style.background  = 'transparent';
    const file = event.dataTransfer.files[0];
    if (file) {
        selectedFile = file;
        document.getElementById('drop-label').textContent = file.name;
        document.getElementById('drop-zone').style.borderColor = 'var(--t500)';
    }
}

async function uploadFile() {
    const res     = document.getElementById('upload-result');
    const siteId  = document.getElementById('site-select').value;
    const driveId = document.getElementById('drive-select').value;
    if (!siteId || !driveId) { showMsg(res, STR.site_drive_req, '#d97706'); return; }
    if (!selectedFile)       { showMsg(res, STR.file_required, '#d97706'); return; }
    showMsg(res, STR.uploading, '#9ca3af');
    const fd = new FormData();
    fd.append('site_id',  siteId);
    fd.append('drive_id', driveId);
    fd.append('file',     selectedFile);
    fd.append('_token',   CSRF);
    try {
        const r = await fetch('{{ route("teams.api.upload") }}', { method:'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
            const link = d.url ? ` — <a href="${d.url}" target="_blank" style="color:#db2777;">${STR.open}</a>` : '';
            showMsg(res, `✓ ${d.name} ${STR.upload_done}${link}`, '#16a34a');
            selectedFile = null;
            document.getElementById('drop-label').textContent = STR.drop_label;
            document.getElementById('drop-zone').style.borderColor = '#e5e7eb';
            document.getElementById('drop-zone').style.background  = 'transparent';
        } else {
            showMsg(res, '✗ ' + d.error, '#dc2626');
        }
    } catch(e) { showMsg(res, STR.upload_fail, '#dc2626'); }
}

// ─── 공통 유틸 ─────────────────────────────────────────────────
function showMsg(el, text, color) {
    el.innerHTML      = text;
    el.style.color    = color;
    el.style.display  = text ? 'block' : 'none';
}

async function apiGet(url) {
    const res = await fetch(url, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
    return res.json();
}

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
    return res.json();
}

async function apiFetch(url, method, body) {
    return fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
}

// ─── 초기화 ────────────────────────────────────────────────────
@if($setting->is_verified)
document.addEventListener('DOMContentLoaded', () => { loadTeams(); loadSites(); });
@endif
</script>
@endsection
