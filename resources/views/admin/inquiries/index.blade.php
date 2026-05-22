@extends('layouts.admin')

@section('title', __('admin.inquiries'))

@section('header-actions')
<button onclick="openSendModal()" class="btn-secondary" style="font-size:12px;padding:6px 14px;">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    {{ __('admin.search_and_send') }}
</button>
<button onclick="openBroadcastModal()" class="btn-primary" style="font-size:12px;padding:6px 14px;">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
    {{ __('admin.broadcast') }}
</button>
@endsection

@section('content')
<div class="admin-stat-grid">
    <div class="admin-stat"><div class="admin-stat-val" style="color:#1e293b;">{{ $stats['all'] }}</div><div class="admin-stat-lbl">{{ __('admin.status_all') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#ef4444;">{{ $stats['open'] }}</div><div class="admin-stat-lbl">{{ __('admin.status_open') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#3b82f6;">{{ $stats['active'] }}</div><div class="admin-stat-lbl">{{ __('admin.status_active') }}</div></div>
    <div class="admin-stat"><div class="admin-stat-val" style="color:#64748b;">{{ $stats['closed'] }}</div><div class="admin-stat-lbl">{{ __('admin.status_closed') }}</div></div>
</div>

<div class="admin-card">
    <form method="GET" action="{{ route('admin.inquiries.index') }}" class="filter-bar">
        @php $gp = $groupId ?? '' @endphp
        <a href="{{ route('admin.inquiries.index', ['status'=>'all','search'=>$search,'group_id'=>$gp]) }}" class="filter-tab {{ $status==='all'?'active':'' }}">{{ __('admin.status_all') }}</a>
        <a href="{{ route('admin.inquiries.index', ['status'=>'open','search'=>$search,'group_id'=>$gp]) }}" class="filter-tab {{ $status==='open'?'active':'' }}">{{ __('admin.status_open') }}</a>
        <a href="{{ route('admin.inquiries.index', ['status'=>'active','search'=>$search,'group_id'=>$gp]) }}" class="filter-tab {{ $status==='active'?'active':'' }}">{{ __('admin.status_active') }}</a>
        <a href="{{ route('admin.inquiries.index', ['status'=>'closed','search'=>$search,'group_id'=>$gp]) }}" class="filter-tab {{ $status==='closed'?'active':'' }}">{{ __('admin.status_closed') }}</a>
        <input type="hidden" name="status" value="{{ $status }}">
        @if($groups->isNotEmpty())
        <select name="group_id" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#334155;background:#fff;outline:none;">
            <option value="">{{ __('admin.all_company') }}</option>
            @foreach($groups as $group)
            <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
            @endforeach
        </select>
        @endif
        <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('admin.inq_search_placeholder') }}">
        <button type="submit" class="btn-primary" style="padding:7px 14px;">{{ __('admin.search_btn') }}</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('admin.col_user') }}</th>
                <th>{{ __('admin.col_last_message') }}</th>
                <th>{{ __('admin.col_status') }}</th>
                <th>{{ __('admin.col_assigned_admin') }}</th>
                <th>{{ __('admin.col_last_updated') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($conversations as $conv)
            @php
                $user = $conv->firstMessage?->sender ?? $conv->participants->first();
                $lastMsg = $conv->lastMessage;
                $badgeClass = match($conv->status) { 'open'=>'badge-red', 'active'=>'badge-blue', default=>'badge-gray' };
                $badgeText  = match($conv->status) { 'open'=>__('admin.status_open'), 'active'=>__('admin.status_active'), default=>__('admin.status_closed') };
            @endphp
            <tr id="conv-row-{{ $conv->id }}" style="transition:background .4s;">
                <td style="color:#94a3b8;font-size:12px;">
                    {{ $conv->id }}
                    <span id="conv-unread-{{ $conv->id }}" style="display:none;margin-left:4px;width:7px;height:7px;border-radius:50%;background:#ef4444;display:none;vertical-align:middle;animation:pulse 1.2s infinite;"></span>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                            {{ $user ? mb_substr($user->name,0,1) : '?' }}
                        </div>
                        <div>
                            <p style="font-size:12px;font-weight:600;color:#1e293b;">{{ $user?->name ?? '-' }}</p>
                            <p style="font-size:11px;color:#94a3b8;">
                                {{ $user?->email ?? '' }}
                                @if($user?->companyGroup)
                                · <span style="color:#6366f1;">{{ $user->companyGroup->name }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </td>
                <td id="conv-last-msg-{{ $conv->id }}" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:#64748b;">
                    {{ $lastMsg ? strip_tags(preg_replace('/^\[관리자 .+?\] /', '', $lastMsg->body)) : '—' }}
                </td>
                <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                <td style="font-size:12px;color:#64748b;">{{ $conv->assignedAdmin?->name ?? '—' }}</td>
                <td id="conv-time-{{ $conv->id }}" style="font-size:12px;color:#94a3b8;">{{ $conv->updated_at->format('m/d H:i') }}</td>
                <td>
                    <a href="{{ route('admin.inquiries.show', $conv) }}" class="btn-secondary" style="padding:5px 10px;font-size:11px;">{{ __('admin.inq_detail_btn') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">{{ __('admin.no_inquiries_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($conversations->hasPages())
    <div style="margin-top:16px;">{{ $conversations->links() }}</div>
    @endif
</div>
@endsection

{{-- ── 전체 발송 모달 ─────────────────────────────────────── --}}
<div id="modal-broadcast" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:96vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#1e293b;">{{ __('admin.inq_broadcast_title') }}</div>
                <div style="font-size:12px;color:#64748b;">{{ __('admin.inq_broadcast_desc') }}</div>
            </div>
            <button onclick="closeBroadcastModal()" style="margin-left:auto;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:16px;">
            {{ __('admin.inq_broadcast_warning') }}
        </div>
        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.inq_msg_content') }}</label>
        <textarea id="broadcast-msg" rows="5" placeholder="{{ __('admin.inq_msg_placeholder') }}"
            style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;outline:none;font-family:inherit;"></textarea>
        <div id="broadcast-error" style="display:none;font-size:12px;color:#dc2626;margin-top:6px;"></div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
            <button onclick="closeBroadcastModal()" class="btn-secondary">{{ __('admin.usr_cancel') }}</button>
            <button onclick="submitBroadcast()" class="btn-primary" id="broadcast-submit-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                {{ __('admin.inq_send_all') }}
            </button>
        </div>
    </div>
</div>

{{-- ── 사용자 검색 발송 모달 ──────────────────────────────── --}}
<div id="modal-send" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:560px;max-width:96vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#1e293b;">{{ __('admin.inq_search_send_title') }}</div>
                <div style="font-size:12px;color:#64748b;">{{ __('admin.inq_search_send_desc') }}</div>
            </div>
            <button onclick="closeSendModal()" style="margin-left:auto;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;line-height:1;">&times;</button>
        </div>

        {{-- 검색 --}}
        <div style="position:relative;margin-bottom:12px;">
            <input type="text" id="user-search-input" placeholder="{{ __('admin.inq_search_placeholder2') }}"
                oninput="searchUsers(this.value)"
                style="width:100%;padding:9px 12px 9px 36px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;">
            <svg style="position:absolute;left:11px;top:50%;transform:translateY(-50%);" width="15" height="15" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        {{-- 검색 결과 --}}
        <div id="user-search-results" style="max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:12px;display:none;"></div>

        {{-- 선택된 사용자 --}}
        <div id="selected-users-wrap" style="display:none;margin-bottom:14px;">
            <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">{{ __('admin.inq_selected_users') }} <span id="selected-count" style="color:#6366f1;">0</span>{{ app()->getLocale() === 'ko' ? '명' : '' }}</div>
            <div id="selected-users-tags" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
        </div>

        {{-- 메시지 --}}
        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.inq_msg_content') }}</label>
        <textarea id="send-msg" rows="4" placeholder="{{ __('admin.inq_msg_placeholder') }}"
            style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;outline:none;font-family:inherit;"></textarea>
        <div id="send-error" style="display:none;font-size:12px;color:#dc2626;margin-top:6px;"></div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
            <button onclick="closeSendModal()" class="btn-secondary">{{ __('admin.usr_cancel') }}</button>
            <button onclick="submitSend()" class="btn-primary" id="send-submit-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                {{ __('admin.inq_send_selected') }}
            </button>
        </div>
    </div>
</div>

@section('scripts')
<style>
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(1.4); }
}
</style>
<script>
const ADMIN_B_STR = {
    no_results:       '{{ __("admin.inq_no_results") }}',
    select_user:      '{{ __("admin.inq_please_select_user") }}',
    enter_msg:        '{{ __("admin.inq_please_enter_msg") }}',
    send_fail:        '{{ __("admin.inq_send_fail") }}',
    network_error:    '{{ __("admin.inq_network_error") }}',
    sending:          '{{ __("admin.inq_sending") }}',
    send_all_btn:     '{{ __("admin.inq_send_all") }}',
    send_selected_btn:'{{ __("admin.inq_send_selected") }}',
    sent_success:     '{{ __("admin.inq_sent_success") }}',
};

(function () {
    const ADMIN_ID = {{ auth('admin')->user()->id }};

    function onNewMessage(data) {
        if (data.is_admin) return;

        const row = document.getElementById('conv-row-' + data.room_id);
        if (!row) return;

        const dot = document.getElementById('conv-unread-' + data.room_id);
        if (dot) dot.style.display = 'inline-block';

        const msgCell = document.getElementById('conv-last-msg-' + data.room_id);
        if (msgCell) {
            const raw = (data.body || '').replace(/^\[관리자 .+?\] /, '');
            const tmp = document.createElement('div');
            tmp.innerHTML = raw;
            msgCell.textContent = tmp.textContent || tmp.innerText || '';
        }

        const timeCell = document.getElementById('conv-time-' + data.room_id);
        if (timeCell) {
            const now = new Date();
            timeCell.textContent = (now.getMonth()+1).toString().padStart(2,'0') + '/' +
                now.getDate().toString().padStart(2,'0') + ' ' +
                now.getHours().toString().padStart(2,'0') + ':' +
                now.getMinutes().toString().padStart(2,'0');
        }

        row.style.background = '#fef9c3';
        setTimeout(() => { row.style.background = ''; }, 3000);

        const tbody = row.parentElement;
        tbody.prepend(row);
    }

    window.addEventListener('adminMessageReceived', function(e) {
        onNewMessage(e.detail);
    });
})();

// ── 전체 발송 모달 ────────────────────────────────────────────
function openBroadcastModal() {
    document.getElementById('broadcast-msg').value = '';
    document.getElementById('broadcast-error').style.display = 'none';
    document.getElementById('modal-broadcast').style.display = 'flex';
}
function closeBroadcastModal() {
    document.getElementById('modal-broadcast').style.display = 'none';
}

async function submitBroadcast() {
    const msg = document.getElementById('broadcast-msg').value.trim();
    const errEl = document.getElementById('broadcast-error');
    errEl.style.display = 'none';

    if (!msg) { errEl.textContent = ADMIN_B_STR.enter_msg; errEl.style.display = 'block'; return; }

    const btn = document.getElementById('broadcast-submit-btn');
    btn.disabled = true;
    btn.textContent = ADMIN_B_STR.sending;

    try {
        const res = await fetch('{{ route('admin.inquiries.broadcast') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: msg }),
        });
        const data = await res.json();
        if (data.ok) {
            closeBroadcastModal();
            alert(`✅ ${data.count}${ADMIN_B_STR.sent_success}`);
        } else {
            errEl.textContent = data.error || ADMIN_B_STR.send_fail;
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = ADMIN_B_STR.network_error;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> ' + ADMIN_B_STR.send_all_btn;
    }
}

// ── 사용자 검색 발송 모달 ─────────────────────────────────────
let selectedUsers = {};
let searchTimer   = null;

function openSendModal() {
    selectedUsers = {};
    document.getElementById('user-search-input').value = '';
    document.getElementById('user-search-results').style.display = 'none';
    document.getElementById('user-search-results').innerHTML = '';
    document.getElementById('send-msg').value = '';
    document.getElementById('send-error').style.display = 'none';
    renderSelectedTags();
    document.getElementById('modal-send').style.display = 'flex';
    setTimeout(() => document.getElementById('user-search-input').focus(), 100);
}
function closeSendModal() {
    document.getElementById('modal-send').style.display = 'none';
}

function searchUsers(q) {
    clearTimeout(searchTimer);
    if (!q.trim()) {
        document.getElementById('user-search-results').style.display = 'none';
        return;
    }
    searchTimer = setTimeout(async () => {
        try {
            const res  = await fetch(`{{ route('admin.inquiries.users.search') }}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const list = await res.json();
            renderSearchResults(list);
        } catch {}
    }, 280);
}

function renderSearchResults(list) {
    const el = document.getElementById('user-search-results');
    if (!list.length) {
        el.innerHTML = `<div style="padding:14px;font-size:13px;color:#94a3b8;text-align:center;">${ADMIN_B_STR.no_results}</div>`;
        el.style.display = 'block';
        return;
    }
    el.innerHTML = list.map(u => `
        <div onclick="toggleUser(${u.id},'${escHtml(u.name)}','${escHtml(u.email)}')"
             id="search-row-${u.id}"
             style="display:flex;align-items:center;gap:12px;padding:9px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s;"
             onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=selectedUsers[${u.id}]?'#eef2ff':''">
            <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                ${escHtml(u.name.charAt(0))}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#1e293b;">${escHtml(u.name)}</div>
                <div style="font-size:11px;color:#94a3b8;">${escHtml(u.email)}${u.company ? ' · ' + escHtml(u.company) : ''}</div>
            </div>
            <div id="check-${u.id}" style="width:18px;height:18px;border-radius:5px;border:2px solid #d1d5db;flex-shrink:0;display:flex;align-items:center;justify-content:center;"></div>
        </div>
    `).join('');
    el.style.display = 'block';

    Object.keys(selectedUsers).forEach(id => highlightSearchRow(id, true));
}

function toggleUser(id, name, email) {
    if (selectedUsers[id]) {
        delete selectedUsers[id];
        highlightSearchRow(id, false);
    } else {
        selectedUsers[id] = { id, name, email };
        highlightSearchRow(id, true);
    }
    renderSelectedTags();
}

function highlightSearchRow(id, selected) {
    const row   = document.getElementById('search-row-' + id);
    const check = document.getElementById('check-' + id);
    if (!row) return;
    row.style.background = selected ? '#eef2ff' : '';
    if (check) {
        check.style.background    = selected ? '#6366f1' : '';
        check.style.borderColor   = selected ? '#6366f1' : '#d1d5db';
        check.innerHTML = selected ? '<svg width="11" height="11" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>' : '';
    }
}

function removeUser(id) {
    delete selectedUsers[id];
    highlightSearchRow(id, false);
    renderSelectedTags();
}

function renderSelectedTags() {
    const tags  = document.getElementById('selected-users-tags');
    const wrap  = document.getElementById('selected-users-wrap');
    const count = document.getElementById('selected-count');
    const ids   = Object.keys(selectedUsers);
    count.textContent = ids.length;
    wrap.style.display = ids.length ? 'block' : 'none';
    tags.innerHTML = ids.map(id => {
        const u = selectedUsers[id];
        return `<span style="display:inline-flex;align-items:center;gap:4px;background:#eef2ff;color:#4f46e5;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:500;">
            ${escHtml(u.name)}
            <button onclick="removeUser(${u.id})" style="background:none;border:none;color:#818cf8;cursor:pointer;font-size:14px;line-height:1;padding:0;">&times;</button>
        </span>`;
    }).join('');
}

async function submitSend() {
    const msg    = document.getElementById('send-msg').value.trim();
    const errEl  = document.getElementById('send-error');
    const ids    = Object.keys(selectedUsers).map(Number);
    errEl.style.display = 'none';

    if (!ids.length) { errEl.textContent = ADMIN_B_STR.select_user; errEl.style.display = 'block'; return; }
    if (!msg)        { errEl.textContent = ADMIN_B_STR.enter_msg;   errEl.style.display = 'block'; return; }

    const btn = document.getElementById('send-submit-btn');
    btn.disabled = true;
    btn.textContent = ADMIN_B_STR.sending;

    try {
        const res  = await fetch('{{ route('admin.inquiries.send') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ user_ids: ids, message: msg }),
        });
        const data = await res.json();
        if (data.ok) {
            closeSendModal();
            alert(`✅ ${data.count}${ADMIN_B_STR.sent_success}`);
        } else {
            errEl.textContent = data.error || ADMIN_B_STR.send_fail;
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = ADMIN_B_STR.network_error;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> ' + ADMIN_B_STR.send_selected_btn;
    }
}

// ESC로 모달 닫기
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    closeBroadcastModal();
    closeSendModal();
});

// 배경 클릭으로 닫기
document.getElementById('modal-broadcast').addEventListener('click', function(e) {
    if (e.target === this) closeBroadcastModal();
});
document.getElementById('modal-send').addEventListener('click', function(e) {
    if (e.target === this) closeSendModal();
});

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endsection
