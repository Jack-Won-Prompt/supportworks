@extends('layouts.admin')

@section('title', __('admin.admin_accounts'))

@section('header-actions')
<button onclick="openAdminModal()" class="btn-primary">+ {{ __('admin.admin_title_add') }}</button>
@endsection

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<div class="admin-card" style="padding:0;overflow:hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>{{ __('admin.col_name_id') }}</th>
                <th>{{ __('admin.col_email') }}</th>
                <th>{{ __('admin.col_role') }}</th>
                <th>{{ __('admin.col_company_assigned') }}</th>
                <th>{{ __('admin.col_status') }}</th>
                <th>{{ __('admin.col_last_login') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($admins as $adminUser)
            @php
                $roleLbl = match($adminUser->role) {
                    'super_admin' => __('admin.role_super_admin'),
                    'admin'       => __('admin.role_admin_label'),
                    'operator'    => __('admin.role_operator_label'),
                    default       => __('admin.role_support_label')
                };
                $roleClass = match($adminUser->role) {
                    'super_admin' => 'badge-red', 'admin' => 'badge-purple',
                    'operator' => 'badge-blue', default => 'badge-gray'
                };
                $statusClass = $adminUser->status === 'active' ? 'badge-green' : ($adminUser->status === 'locked' ? 'badge-red' : 'badge-gray');
                $statusLbl   = $adminUser->status === 'active' ? __('admin.active') : ($adminUser->status === 'locked' ? __('admin.status_locked') : __('admin.inactive'));
            @endphp
            <tr id="admin-row-{{ $adminUser->id }}">
                <td>
                    <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $adminUser->name }}</div>
                    <div style="font-size:11px;color:#94a3b8;font-family:monospace;">{{ $adminUser->login_id }}</div>
                </td>
                <td style="font-size:12px;color:#64748b;">{{ $adminUser->email }}</td>
                <td><span class="badge {{ $roleClass }}">{{ $roleLbl }}</span></td>
                <td>
                    @if($adminUser->companyGroups->isEmpty())
                    <span style="font-size:12px;color:#94a3b8;">{{ __('admin.unassigned') }}</span>
                    @else
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($adminUser->companyGroups->take(3) as $g)
                        <span style="font-size:10px;background:#f1f5f9;color:#475569;padding:2px 7px;border-radius:10px;">{{ $g->name }}</span>
                        @endforeach
                        @if($adminUser->companyGroups->count() > 3)
                        <span style="font-size:10px;color:#94a3b8;">+{{ $adminUser->companyGroups->count() - 3 }}</span>
                        @endif
                    </div>
                    @endif
                </td>
                <td><span class="badge {{ $statusClass }}">{{ $statusLbl }}</span></td>
                <td style="font-size:12px;color:#64748b;">
                    {{ $adminUser->last_login_at ? $adminUser->last_login_at->format('m/d H:i') : '-' }}
                </td>
                <td>
                    @if($adminUser->role !== 'super_admin')
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button onclick="openAdminModal({{ json_encode([
                            'id'          => $adminUser->id,
                            'name'        => $adminUser->name,
                            'login_id'    => $adminUser->login_id,
                            'email'       => $adminUser->email,
                            'role'        => $adminUser->role,
                            'status'      => $adminUser->status,
                            'group_ids'   => $adminUser->companyGroups->pluck('id'),
                            'update_url'  => route('admin.admins.update', $adminUser),
                        ]) }})"
                            style="font-size:12px;color:#6366f1;background:none;border:none;cursor:pointer;padding:0;">{{ __('admin.edit') }}</button>
                        @if(auth('admin')->id() !== $adminUser->id)
                        @php $deactivateConfirm = __('admin.confirm_deactivate_admin'); @endphp
                        <form method="POST" action="{{ route('admin.admins.destroy', $adminUser) }}"
                              onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $deactivateConfirm }}">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12px;color:#ef4444;background:none;border:none;cursor:pointer;">{{ __('admin.deactivate') }}</button>
                        </form>
                        @endif
                    </div>
                    @else
                    <span style="font-size:11px;color:#94a3b8;">{{ __('admin.protected') }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">{{ __('admin.no_admins_registered') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($admins->hasPages())
<div style="margin-top:16px;">{{ $admins->links() }}</div>
@endif

{{-- 추가/수정 모달 --}}
<div id="admin-modal" onclick="if(event.target===this)closeAdminModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:600px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px 14px;border-bottom:1px solid #f1f5f9;position:sticky;top:0;background:#fff;z-index:1;">
            <h3 id="am-title" style="font-size:15px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.admin_title_add') }}</h3>
            <button onclick="closeAdminModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:20px;line-height:1;">×</button>
        </div>
        <div style="padding:22px 24px 26px;display:flex;flex-direction:column;gap:12px;">
            <div id="am-error" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;font-size:12px;color:#dc2626;"></div>

            {{-- 이름 / 로그인ID --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.name_label') }} <span style="color:#ef4444;">*</span></label>
                    <input id="am-name" type="text" placeholder="{{ __('admin.name_label') }}"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div id="am-loginid-wrap">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.login_id_required_label') }} <span style="color:#ef4444;">*</span></label>
                    <input id="am-login-id" type="text" placeholder="{{ __('admin.login_id_hint_valid') }}"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    <p id="am-loginid-hint" style="font-size:11px;color:#94a3b8;margin:3px 0 0;"></p>
                </div>
            </div>

            {{-- 이메일 --}}
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
                <input id="am-email" type="email" placeholder="email@example.com"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            {{-- 비밀번호 --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label id="am-pw-label" style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_label') }} <span style="color:#ef4444;">*</span></label>
                    <input id="am-password" type="password" placeholder="{{ __('admin.password_min_ph') }}"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_confirm_label') }}</label>
                    <input id="am-password-confirm" type="password"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
            </div>

            {{-- 역할 / 상태 --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.role_label') }} <span style="color:#ef4444;">*</span></label>
                    <select id="am-role" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;outline:none;">
                        <option value="admin">{{ __('admin.role_option_admin') }}</option>
                        <option value="operator">{{ __('admin.role_option_operator') }}</option>
                        <option value="support_agent">{{ __('admin.role_option_support_agent') }}</option>
                    </select>
                </div>
                <div id="am-status-wrap">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.status_label') }}</label>
                    <select id="am-status" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;outline:none;">
                        <option value="active">{{ __('admin.status_option_active') }}</option>
                        <option value="inactive">{{ __('admin.status_option_inactive') }}</option>
                    </select>
                </div>
            </div>

            {{-- 담당 회사 배정 --}}
            @if($groups->isNotEmpty())
            <div style="border-top:1px solid #f1f5f9;padding-top:14px;">
                <h4 style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">{{ __('admin.assign_company') }}</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="am-groups">
                    @foreach($groups as $group)
                    <label id="am-grp-lbl-{{ $group->id }}" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;background:#fff;">
                        <input type="checkbox" class="am-group-cb" value="{{ $group->id }}"
                            style="width:15px;height:15px;accent-color:#6366f1;"
                            onchange="var l=this.closest('label');l.style.background=this.checked?'#eef2ff':'#fff';l.style.borderColor=this.checked?'#c7d2fe':'#e2e8f0'">
                        <span style="font-size:12px;font-weight:500;color:#334155;">{{ $group->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px;border-top:1px solid #f1f5f9;">
                <button onclick="closeAdminModal()" style="padding:8px 18px;font-size:13px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">{{ __('admin.cancel') }}</button>
                <button id="am-submit" onclick="submitAdminModal()" style="padding:8px 22px;font-size:13px;font-weight:600;color:#fff;background:#6366f1;border:none;border-radius:8px;cursor:pointer;">{{ __('admin.create') }}</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
const ADMIN_AM_STR = {
    titleAdd:         '{{ __("admin.admin_title_add") }}',
    titleEdit:        '{{ __("admin.admin_title_edit") }}',
    save:             '{{ __("admin.save") }}',
    create:           '{{ __("admin.create") }}',
    processing:       '{{ __("admin.processing") }}',
    pwNewLabel:       '{{ __("admin.password_new_label") }}',
    pwLabel:          '{{ __("admin.password_label") }}',
    errNameEmail:     '{{ __("admin.am_error_name_email") }}',
    errLoginRequired: '{{ __("admin.am_error_login_id_required") }}',
    errLoginFormat:   '{{ __("admin.am_error_login_id_format") }}',
    errPwRequired:    '{{ __("admin.am_error_password_required") }}',
    errPwMin:         '{{ __("admin.am_error_password_min") }}',
    errPwMatch:       '{{ __("admin.am_error_password_match") }}',
    errGeneric:       '{{ __("admin.am_error_generic") }}',
};

const _amCsrf = '{{ csrf_token() }}';
const _amStoreUrl = '{{ route("admin.admins.store") }}';
let _amEditUrl = null;
let _amIsEdit = false;

async function openAdminModal(data) {
    _amIsEdit  = !!data;
    _amEditUrl = data?.update_url || null;

    document.getElementById('am-title').textContent   = _amIsEdit ? ADMIN_AM_STR.titleEdit : ADMIN_AM_STR.titleAdd;
    document.getElementById('am-submit').textContent  = _amIsEdit ? ADMIN_AM_STR.save : ADMIN_AM_STR.create;
    document.getElementById('am-error').style.display = 'none';

    // 로그인ID 필드: 추가 시만 표시
    document.getElementById('am-loginid-wrap').style.display = _amIsEdit ? 'none' : '';
    // 상태: 수정 시만 표시
    document.getElementById('am-status-wrap').style.display  = _amIsEdit ? '' : 'none';
    // 비밀번호 필수 표시
    document.getElementById('am-pw-label').innerHTML = _amIsEdit
        ? ADMIN_AM_STR.pwNewLabel
        : ADMIN_AM_STR.pwLabel + ' <span style="color:#ef4444;">*</span>';

    // 필드 초기화
    document.getElementById('am-name').value             = data?.name     || '';
    document.getElementById('am-login-id').value         = '';
    document.getElementById('am-email').value            = data?.email    || '';
    document.getElementById('am-password').value         = '';
    document.getElementById('am-password-confirm').value = '';
    document.getElementById('am-role').value             = data?.role     || 'admin';
    document.getElementById('am-status').value           = data?.status   || 'active';

    // 회사 그룹 체크박스 초기화
    document.querySelectorAll('.am-group-cb').forEach(cb => {
        const checked = data?.group_ids ? data.group_ids.includes(parseInt(cb.value)) : false;
        cb.checked = checked;
        const lbl = cb.closest('label');
        lbl.style.background  = checked ? '#eef2ff' : '#fff';
        lbl.style.borderColor = checked ? '#c7d2fe' : '#e2e8f0';
    });

    document.getElementById('admin-modal').style.display = 'flex';
}

async function closeAdminModal() {
    document.getElementById('admin-modal').style.display = 'none';
}

async function submitAdminModal() {
    const name    = document.getElementById('am-name').value.trim();
    const email   = document.getElementById('am-email').value.trim();
    const pw      = document.getElementById('am-password').value;
    const pwc     = document.getElementById('am-password-confirm').value;
    const loginId = document.getElementById('am-login-id').value.trim();
    const role    = document.getElementById('am-role').value;
    const status  = document.getElementById('am-status').value;

    // 클라이언트 유효성 검사
    if (!name || !email) { showAmError(ADMIN_AM_STR.errNameEmail); return; }
    if (!_amIsEdit && !loginId) { showAmError(ADMIN_AM_STR.errLoginRequired); return; }
    if (!_amIsEdit && !/^[A-Za-z0-9_]+$/.test(loginId)) { showAmError(ADMIN_AM_STR.errLoginFormat); return; }
    if (!_amIsEdit && !pw) { showAmError(ADMIN_AM_STR.errPwRequired); return; }
    if (pw && pw.length < 8) { showAmError(ADMIN_AM_STR.errPwMin); return; }
    if (pw && pw !== pwc) { showAmError(ADMIN_AM_STR.errPwMatch); return; }

    const groupIds = Array.from(document.querySelectorAll('.am-group-cb:checked')).map(cb => parseInt(cb.value));

    const payload = { name, email, role, group_ids: groupIds };
    if (!_amIsEdit) payload.login_id = loginId;
    if (_amIsEdit) payload.status = status;
    if (pw) { payload.password = pw; payload.password_confirmation = pwc; }

    const btn = document.getElementById('am-submit');
    btn.disabled = true;
    btn.textContent = ADMIN_AM_STR.processing;

    const url    = _amIsEdit ? _amEditUrl : _amStoreUrl;
    const method = _amIsEdit ? 'PATCH' : 'POST';

    const res = await fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': _amCsrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });
    const d = await res.json().catch(() => ({}));

    btn.disabled = false;
    btn.textContent = _amIsEdit ? ADMIN_AM_STR.save : ADMIN_AM_STR.create;

    if (!res.ok || !d.ok) {
        const msg = d.errors
            ? Object.values(d.errors).flat().join('\n')
            : (d.message || ADMIN_AM_STR.errGeneric);
        showAmError(msg);
        return;
    }

    closeAdminModal();
    location.reload();
}

async function showAmError(msg) {
    const el = document.getElementById('am-error');
    el.textContent = msg;
    el.style.display = 'block';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAdminModal(); });
</script>
@endsection
