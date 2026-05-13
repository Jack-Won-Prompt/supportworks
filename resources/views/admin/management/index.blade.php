@extends('layouts.admin')
@section('title', __('admin.admin_manage'))

@section('header-actions')
@if(auth('admin')->user()->isSuperAdmin())
<button onclick="openInviteModal()"
    style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
    {{ __('admin.admin_invite') }}
</button>
@endif
@endsection

@section('content')

@if(session('success'))
<div style="margin-bottom:16px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;font-size:13px;display:flex;align-items:center;gap:8px;">
    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- ── 관리자 목록 ── --}}
<div class="admin-card" style="padding:0;overflow:hidden;margin-bottom:20px;">
    <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:14px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.mgmt_admin_list') }}</h3>
        <span style="font-size:12px;color:#94a3b8;">{{ __('admin.mgmt_total_count', ['count' => $admins->count()]) }}</span>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>{{ __('admin.mgmt_name_id_col') }}</th>
                <th>{{ __('admin.col_email') }}</th>
                <th>{{ __('admin.col_role') }}</th>
                <th>{{ __('admin.col_status') }}</th>
                <th>{{ __('admin.mgmt_assigned_projects') }}</th>
                <th style="text-align:center;">{{ __('admin.mgmt_project_assign_col') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($admins as $adm)
            @php
                $roleLbl   = match($adm->role) { 'super_admin'=>__('admin.mgmt_role_super_admin'),'admin'=>__('admin.role_admin'),'operator'=>__('admin.operator'),default=>__('admin.support_agent') };
                $roleClass = match($adm->role) { 'super_admin'=>'badge-red','admin'=>'badge-purple','operator'=>'badge-blue',default=>'badge-gray' };
                $stClass   = $adm->status === 'active' ? 'badge-green' : ($adm->status === 'locked' ? 'badge-red' : 'badge-gray');
                $stLbl     = match($adm->status) { 'active'=>__('admin.mgmt_status_active'),'locked'=>__('admin.mgmt_status_locked'),default=>__('admin.mgmt_status_inactive') };
            @endphp
            <tr>
                <td>
                    <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $adm->name }}</div>
                    <div style="font-size:11px;color:#94a3b8;font-family:monospace;">{{ $adm->login_id }}</div>
                </td>
                <td style="font-size:12px;color:#64748b;">{{ $adm->email }}</td>
                <td><span class="badge {{ $roleClass }}">{{ $roleLbl }}</span></td>
                <td><span class="badge {{ $stClass }}">{{ $stLbl }}</span></td>
                <td>
                    @if($adm->projects->isEmpty())
                    <span id="proj-label-{{ $adm->id }}" style="font-size:12px;color:#94a3b8;">{{ __('admin.unassigned') }}</span>
                    @else
                    <div id="proj-label-{{ $adm->id }}" style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($adm->projects->take(3) as $p)
                        <span style="font-size:10px;background:#ede9fe;color:#6d28d9;padding:2px 7px;border-radius:10px;">{{ $p->name }}</span>
                        @endforeach
                        @if($adm->projects->count() > 3)
                        <span style="font-size:10px;color:#94a3b8;">+{{ $adm->projects->count() - 3 }}</span>
                        @endif
                    </div>
                    @endif
                </td>
                <td style="text-align:center;">
                    @if($adm->role !== 'super_admin' && auth('admin')->user()->isSuperAdmin())
                    <button onclick="openProjectModal({{ $adm->id }}, '{{ addslashes($adm->name) }}', {{ json_encode($adm->projects->pluck('id')) }})"
                        style="padding:4px 12px;font-size:11px;font-weight:600;background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;border-radius:6px;cursor:pointer;transition:background .1s;"
                        onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                        {{ __('admin.mgmt_assign') }}
                    </button>
                    @else
                    <span style="font-size:11px;color:#94a3b8;">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">{{ __('admin.mgmt_no_admins') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── 대기 중 초대 ── --}}
@if(auth('admin')->user()->isSuperAdmin())
<div class="admin-card" style="padding:0;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:14px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.mgmt_invite_status') }}</h3>
        <span style="font-size:12px;color:#94a3b8;">{{ __('admin.mgmt_total_invites', ['count' => $pendingInvitations->count()]) }}</span>
    </div>
    @if($pendingInvitations->isEmpty())
    <div style="padding:32px;text-align:center;font-size:13px;color:#94a3b8;">{{ __('admin.mgmt_no_pending_invites') }}</div>
    @else
    <table class="admin-table">
        <thead>
            <tr>
                <th>{{ __('admin.col_name') }}</th>
                <th>{{ __('admin.col_email') }}</th>
                <th>{{ __('admin.col_role') }}</th>
                <th>{{ __('admin.mgmt_inviter_col') }}</th>
                <th>{{ __('admin.mgmt_expire_col') }}</th>
                <th>{{ __('admin.mgmt_status_col') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingInvitations as $inv)
            @php
                $isExpired = $inv->expires_at->isPast();
                $invRoleLbl = match($inv->role) { 'admin'=>__('admin.role_admin'),'operator'=>__('admin.operator'),default=>__('admin.support_agent') };
            @endphp
            <tr>
                <td style="font-size:13px;font-weight:500;color:#1e293b;">{{ $inv->name }}</td>
                <td style="font-size:12px;color:#64748b;">{{ $inv->email }}</td>
                <td><span class="badge badge-blue">{{ $invRoleLbl }}</span></td>
                <td style="font-size:12px;color:#64748b;">{{ $inv->invitedBy?->name ?? '-' }}</td>
                <td style="font-size:12px;color:{{ $isExpired ? '#ef4444' : '#64748b' }};">
                    {{ $inv->expires_at->format('Y-m-d H:i') }}
                    @if($isExpired)<span style="font-size:10px;margin-left:4px;">{{ __('admin.mgmt_expired_mark') }}</span>@endif
                </td>
                <td>
                    <span class="badge {{ $isExpired ? 'badge-gray' : 'badge-orange' }}">
                        {{ $isExpired ? __('admin.mgmt_expired') : __('admin.mgmt_pending_status') }}
                    </span>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.management.invite.cancel', $inv) }}"
                          onsubmit="return confirm('{{ __('admin.mgmt_cancel_invite_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            style="font-size:11px;color:#ef4444;background:none;border:none;cursor:pointer;padding:2px 4px;">
                            {{ __('admin.mgmt_invite_cancel') }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endif

{{-- ── 초대 모달 ── --}}
<div id="invite-overlay" style="display:none;position:fixed;inset:0;background:rgba(30,27,46,.4);z-index:9999;backdrop-filter:blur(2px);" onclick="closeInviteModal()"></div>
<div id="invite-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10000;background:#fff;border-radius:16px;padding:28px;width:440px;max-width:95vw;box-shadow:0 16px 48px rgba(0,0,0,.12);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="font-size:16px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.admin_invite') }}</h3>
        <button onclick="closeInviteModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:22px;line-height:1;">&times;</button>
    </div>
    <form method="POST" action="{{ route('admin.management.invite') }}">
        @csrf
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_name') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" name="name" required placeholder="{{ __('admin.person_name_ph') }}"
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
            <input type="email" name="email" required placeholder="admin@example.com"
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;transition:border-color .15s;"
                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div style="margin-bottom:22px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_role') }} <span style="color:#ef4444;">*</span></label>
            <select name="role" required
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;">
                <option value="admin">{{ __('admin.role_admin') }}</option>
                <option value="operator">{{ __('admin.operator') }}</option>
                <option value="support_agent" selected>{{ __('admin.support_agent') }}</option>
            </select>
        </div>
        <p style="font-size:11.5px;color:#94a3b8;margin-bottom:18px;line-height:1.6;">
            {!! __('admin.mgmt_invite_link_note') !!}
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="closeInviteModal()"
                style="padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:9px;background:#fff;font-size:13px;font-weight:500;color:#64748b;cursor:pointer;">{{ __('admin.mgmt_invite_cancel') }}</button>
            <button type="submit"
                style="padding:8px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-size:13px;font-weight:600;cursor:pointer;">{{ __('admin.mgmt_invite_send') }}</button>
        </div>
    </form>
</div>

{{-- ── 프로젝트 배정 모달 ── --}}
<div id="proj-overlay" style="display:none;position:fixed;inset:0;background:rgba(30,27,46,.4);z-index:9999;backdrop-filter:blur(2px);" onclick="closeProjModal()"></div>
<div id="proj-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10000;background:#fff;border-radius:16px;padding:28px;width:480px;max-width:95vw;box-shadow:0 16px 48px rgba(0,0,0,.12);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
        <h3 id="proj-modal-title" style="font-size:16px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.mgmt_project_assign') }}</h3>
        <button onclick="closeProjModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:22px;line-height:1;">&times;</button>
    </div>
    <p style="font-size:12px;color:#94a3b8;margin:0 0 16px;">{{ __('admin.mgmt_project_visible_note') }}</p>

    <div style="border:1.5px solid #e2e8f0;border-radius:10px;max-height:300px;overflow-y:auto;padding:8px;">
        @forelse($projects as $proj)
        @php
            $statusColor = match($proj->status) { 'active'=>'#22c55e','completed'=>'#94a3b8',default=>'#f59e0b' };
            $statusLbl   = match($proj->status) { 'active'=>__('admin.maint_status_in_progress'),'completed'=>__('admin.maint_status_completed'),default=>__('admin.status_pending') };
        @endphp
        <label style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:background .1s;"
               onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
            <input type="checkbox" class="proj-check" value="{{ $proj->id }}"
                style="accent-color:#6366f1;width:15px;height:15px;flex-shrink:0;cursor:pointer;">
            <span style="font-size:13px;color:#1e293b;flex:1;">{{ $proj->name }}</span>
            <span style="font-size:10px;font-weight:600;padding:1px 7px;border-radius:10px;background:{{ $statusColor }}22;color:{{ $statusColor }};">{{ $statusLbl }}</span>
        </label>
        @empty
        <p style="text-align:center;font-size:13px;color:#94a3b8;padding:20px 0;">{{ __('admin.mgmt_no_projects') }}</p>
        @endforelse
    </div>

    <div style="display:flex;align-items:center;gap:8px;margin-top:16px;">
        <span id="proj-selected-count" style="font-size:12px;color:#6366f1;font-weight:500;flex:1;"></span>
        <button type="button" onclick="closeProjModal()"
            style="padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:9px;background:#fff;font-size:13px;font-weight:500;color:#64748b;cursor:pointer;">{{ __('admin.mgmt_invite_cancel') }}</button>
        <button type="button" onclick="submitProjects()"
            style="padding:8px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-size:13px;font-weight:600;cursor:pointer;">{{ __('admin.usr_save') }}</button>
    </div>
</div>

<script>
const ADMIN_B_STR = {
    unassigned:     '{{ __("admin.unassigned") }}',
    selected_count: '{{ __("admin.mgmt_selected_count") }}',
    proj_assign:    '{{ __("admin.mgmt_project_assign") }}',
};

const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let currentAdminId = null;

// ── 초대 모달 ──────────────────────────────────────────────
async function openInviteModal() {
    document.getElementById('invite-overlay').style.display = 'block';
    document.getElementById('invite-modal').style.display   = 'block';
}
async function closeInviteModal() {
    document.getElementById('invite-overlay').style.display = 'none';
    document.getElementById('invite-modal').style.display   = 'none';
}

// ── 프로젝트 배정 모달 ──────────────────────────────────────
async function openProjectModal(adminId, adminName, assignedIds) {
    currentAdminId = adminId;
    document.getElementById('proj-modal-title').textContent = adminName + ' — ' + ADMIN_B_STR.proj_assign;

    // 체크박스 초기화
    document.querySelectorAll('.proj-check').forEach(cb => {
        cb.checked = assignedIds.includes(parseInt(cb.value));
    });
    updateProjCount();

    document.getElementById('proj-overlay').style.display = 'block';
    document.getElementById('proj-modal').style.display   = 'block';
}
async function closeProjModal() {
    document.getElementById('proj-overlay').style.display = 'none';
    document.getElementById('proj-modal').style.display   = 'none';
    currentAdminId = null;
}
async function updateProjCount() {
    const n = document.querySelectorAll('.proj-check:checked').length;
    document.getElementById('proj-selected-count').textContent = n > 0 ? `${n}${ADMIN_B_STR.selected_count}` : '';
}
document.querySelectorAll('.proj-check').forEach(cb => cb.addEventListener('change', updateProjCount));

async function submitProjects() {
    if (!currentAdminId) return;

    const checked = [...document.querySelectorAll('.proj-check:checked')].map(cb => parseInt(cb.value));

    fetch(`/admin/management/admins/${currentAdminId}/projects`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ project_ids: checked }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) { location.reload(); return; }
        closeProjModal();
        // 테이블 셀 업데이트
        const cell = document.getElementById('proj-label-' + currentAdminId);
        if (cell) {
            if (checked.length === 0) {
                cell.outerHTML = `<span id="proj-label-${currentAdminId}" style="font-size:12px;color:#94a3b8;">${ADMIN_B_STR.unassigned}</span>`;
            } else {
                const names = [...document.querySelectorAll('.proj-check:checked')]
                    .map(cb => cb.closest('label').querySelector('span').textContent.trim())
                    .slice(0, 3);
                const extra = checked.length > 3 ? `<span style="font-size:10px;color:#94a3b8;">+${checked.length - 3}</span>` : '';
                cell.outerHTML = `<div id="proj-label-${currentAdminId}" style="display:flex;flex-wrap:wrap;gap:4px;">
                    ${names.map(n=>`<span style="font-size:10px;background:#ede9fe;color:#6d28d9;padding:2px 7px;border-radius:10px;">${n}</span>`).join('')}
                    ${extra}
                </div>`;
            }
        }
        // 버튼의 onclick 인수 갱신
        const btn = document.querySelector(`button[onclick*="openProjectModal(${currentAdminId},"]`);
        if (btn) {
            btn.setAttribute('onclick', btn.getAttribute('onclick').replace(/\[.*?\]/, JSON.stringify(checked)));
        }
    })
    .catch(() => location.reload());
}
</script>
@endsection
