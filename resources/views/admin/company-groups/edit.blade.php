@extends('layouts.admin')

@section('title', $companyGroup->name . ' ' . __('admin.edit'))

@section('header-actions')
<a href="{{ route('admin.company-groups.index') }}" class="btn-secondary">{{ __('admin.back_to_list') }}</a>
@endsection

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    {{-- ?쇱そ: 湲곕낯 ?뺣낫 + ?대떦 愿由ъ옄 --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- 湲곕낯 ?뺣낫 ??--}}
        <div class="admin-card">
            <h3 style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:16px;">{{ __('admin.basic_info') }}</h3>
            <form method="POST" action="{{ route('admin.company-groups.update', $companyGroup) }}">
                @csrf @method('PATCH')

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.company_name') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $companyGroup->name) }}" required
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('name')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_code') }}</label>
                    <input type="text" value="{{ $companyGroup->code }}" disabled
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;background:#f8fafc;color:#64748b;box-sizing:border-box;">
                    <p style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ __('admin.code_immutable') }}</p>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_description') }}</label>
                    <textarea name="description" rows="3"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">{{ old('description', $companyGroup->description) }}</textarea>
                </div>

                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ $companyGroup->is_active ? 'checked' : '' }}
                        style="width:16px;height:16px;accent-color:#6366f1;">
                    <label for="is_active" style="font-size:13px;color:#334155;cursor:pointer;">{{ __('admin.active_status') }}</label>
                </div>

                <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                    <input type="hidden" name="uses_withworks" value="0">
                    <input type="checkbox" name="uses_withworks" id="uses_withworks" value="1"
                        {{ $companyGroup->uses_withworks ? 'checked' : '' }}
                        style="width:16px;height:16px;accent-color:#7c3aed;">
                    <label for="uses_withworks" style="font-size:13px;color:#334155;cursor:pointer;">WITHWORKS 사용 회사 <span style="font-size:11px;color:#94a3b8;">(공지사항·알림 대상 필터)</span></label>
                </div>

                {{-- ?대떦 愿由ъ옄 ?좊떦 --}}
                <div style="border-top:1px solid #f1f5f9;padding-top:16px;margin-top:4px;">
                    <h4 style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">{{ __('admin.assign_admin_label') }}</h4>
                    @if($allAdmins->isEmpty())
                    <p style="font-size:12px;color:#94a3b8;">{{ __('admin.no_admin_accounts') }}</p>
                    @else
                    <div style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;">
                        @foreach($allAdmins as $adminUser)
                        @php $assigned = in_array($adminUser->id, $assignedAdminIds); @endphp
                        <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;background:{{ $assigned ? '#eef2ff' : '#f8fafc' }};cursor:pointer;">
                            <input type="checkbox" name="admin_ids[]" value="{{ $adminUser->id }}"
                                {{ $assigned ? 'checked' : '' }}
                                style="width:15px;height:15px;accent-color:#6366f1;">
                            <div style="flex:1;">
                                <span style="font-size:12px;font-weight:600;color:#1e293b;">{{ $adminUser->name }}</span>
                                <span style="font-size:11px;color:#94a3b8;margin-left:6px;">{{ $adminUser->login_id }}</span>
                            </div>
                            @php
                                $roleLbl = match($adminUser->role) {
                                    'super_admin' => __('admin.role_super_admin'),
                                    'admin'       => __('admin.role_admin_label'),
                                    'operator'    => __('admin.role_operator_label'),
                                    default       => __('admin.role_support_label')
                                };
                            @endphp
                            <span class="badge {{ $adminUser->role === 'super_admin' ? 'badge-red' : ($adminUser->role === 'admin' ? 'badge-blue' : 'badge-gray') }}">{{ $roleLbl }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- 硫붾돱 湲곕뒫 ?ㅼ젙 --}}
                <div style="border-top:1px solid #f1f5f9;padding-top:16px;margin-top:4px;">
                    <h4 style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">{{ __('admin.menu_feature_settings') }}</h4>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        @foreach(\App\Models\CompanyGroup::FEATURE_KEYS as $key => $label)
                        @php $checked = $companyGroup->hasFeature($key); @endphp
                        <label style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:7px;background:{{ $checked ? '#eef2ff' : '#f8fafc' }};cursor:pointer;transition:background .12s;">
                            <input type="hidden" name="features_{{ $key }}" value="0">
                            <input type="checkbox" name="features_{{ $key }}" value="1"
                                {{ $checked ? 'checked' : '' }}
                                style="width:15px;height:15px;accent-color:#6366f1;"
                                onchange="this.closest('label').style.background=this.checked?'#eef2ff':'#f8fafc'">
                            <span style="font-size:12px;font-weight:600;color:#334155;">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p style="font-size:11px;color:#94a3b8;margin-top:8px;">{{ __('admin.menu_feature_hint') }}</p>
                </div>

                <div style="display:flex;gap:12px;padding-top:16px;border-top:1px solid #f1f5f9;margin-top:16px;">
                    <button type="submit" class="btn-primary">{{ __('admin.save') }}</button>
                    <a href="{{ route('admin.company-groups.index') }}" class="btn-secondary">{{ __('admin.cancel') }}</a>
                </div>
            </form>
        </div>

        {{-- ?듦퀎 移대뱶 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="admin-stat">
                <div class="admin-stat-val" style="color:#6366f1;">{{ $companyGroup->users_count }}</div>
                <div class="admin-stat-lbl">{{ __('admin.stat_group_users') }}</div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-val" style="color:#0891b2;">{{ $companyGroup->admin_users_count }}</div>
                <div class="admin-stat-lbl">{{ __('admin.col_assigned_admin') }}</div>
            </div>
        </div>
    </div>

    {{-- ?ㅻⅨ履? ?뚯냽 ?ъ슜??紐⑸줉 --}}
    <div class="admin-card" style="padding:0;overflow:hidden;" x-data="userAssign()">

        <div style="padding:16px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:13px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.users_in_group') }}</h3>
            <div style="display:flex;gap:8px;">
                <button type="button" onclick="openCgInviteModal()" style="font-size:11px;padding:5px 12px;background:#7c3aed;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;">{{ __('admin.invite_email_modal') }}</button>
                <button type="button" @click="openModal = true" class="btn-primary" style="font-size:11px;padding:5px 10px;">+ {{ __('admin.add_user') }}</button>
            </div>
        </div>

        {{-- ?ъ슜??紐⑸줉 --}}
        <div style="max-height:480px;overflow-y:auto;">
            <table class="admin-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>{{ __('admin.col_name') }}</th>
                        <th>{{ __('admin.col_email') }}</th>
                        <th>{{ __('admin.col_role') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    @forelse($groupUsers as $u)
                    <tr id="user-row-{{ $u->id }}">
                        <td style="font-weight:500;">{{ $u->name }}</td>
                        <td style="color:#64748b;font-size:12px;">{{ $u->email }}</td>
                        <td>
                            <select onchange="updateUserRole({{ $u->id }}, this)"
                                style="padding:3px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#374151;background:#fff;outline:none;cursor:pointer;">
                                <option value="member"  {{ $u->role === 'member'  ? 'selected' : '' }}>{{ __('admin.role_member') }}</option>
                                <option value="admin"   {{ $u->role === 'admin'   ? 'selected' : '' }}>{{ __('admin.role_admin') }}</option>
                                <option value="client"  {{ $u->role === 'client'  ? 'selected' : '' }}>{{ __('admin.role_client') }}</option>
                            </select>
                        </td>
                        <td>
                            <button type="button" onclick="removeUser({{ $u->id }}, this)" style="font-size:11px;color:#ef4444;background:none;border:none;cursor:pointer;">{{ __('admin.remove') }}</button>
                        </td>
                    </tr>
                    @empty
                    <tr id="empty-row">
                        <td colspan="4" style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">{{ __('admin.no_group_users') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ?ъ슜??異붽? 紐⑤떖 --}}
        <div x-show="openModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;" @click.self="openModal=false">
            <div style="background:#fff;border-radius:14px;width:440px;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h4 style="font-size:14px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.add_user_modal_title') }}</h4>
                    <button @click="openModal=false" style="background:none;border:none;font-size:18px;color:#94a3b8;cursor:pointer;">횞</button>
                </div>
                <p style="font-size:12px;color:#64748b;margin-bottom:12px;">{{ __('admin.add_user_modal_desc') }}</p>

                <input type="text" x-model="searchQ" @input.debounce.300ms="doSearch()" placeholder="{{ __('admin.search_user_placeholder') }}"
                    style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;margin-bottom:12px;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">

                <div style="max-height:220px;overflow-y:auto;border:1px solid #f1f5f9;border-radius:8px;">
                    <template x-if="searchResults.length === 0 && searchQ.length > 0">
                        <p style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">{{ __('admin.no_search_results') }}</p>
                    </template>
                    <template x-for="u in searchResults" :key="u.id">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #f8fafc;">
                            <div>
                                <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0;" x-text="u.name"></p>
                                <p style="font-size:11px;color:#94a3b8;margin:2px 0 0;" x-text="u.email + (u.company ? ' 쨌 ' + u.company : '')"></p>
                            </div>
                            <button type="button" @click="addUser(u)" class="btn-primary" style="font-size:11px;padding:4px 10px;">{{ __('admin.add') }}</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ?대찓??珥덈? 紐⑤떖 --}}
<div id="cg-invite-modal" onclick="if(event.target===this)closeCgInviteModal()" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;" onclick="event.stopPropagation()">
        <div style="padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h3 style="font-size:15px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.invite_email_modal') }}</h3>
                <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;"><span style="color:#7c3aed;font-weight:600;">{{ $companyGroup->name }}</span> {{ __('admin.invite_group_suffix') }}</p>
            </div>
            <button onclick="closeCgInviteModal()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;line-height:1;">??/button>
        </div>
        <form method="POST" action="{{ route('admin.users.invite') }}" id="cg-invite-form">
            @csrf
            <input type="hidden" name="company_group_id" value="{{ $companyGroup->id }}">
            <div style="padding:18px 22px;display:flex;flex-direction:column;gap:12px;">

                @if(session('mail_error'))
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:12px;color:#dc2626;">
                    {{ session('mail_error') }}<br>
                    <a href="{{ session('invite_link') }}" target="_blank" style="color:#7c3aed;word-break:break-all;font-size:11px;">{{ session('invite_link') }}</a>
                </div>
                @endif

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" required placeholder="invite@example.com"
                           style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">{{ __('admin.invite_message_label') }} <span style="font-weight:400;color:#94a3b8;">{{ __('admin.optional') }}</span></label>
                    <textarea name="message" rows="2" maxlength="500" placeholder="{{ __('admin.invite_msg_placeholder') }}"
                              style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:none;outline:none;box-sizing:border-box;"
                              onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

            </div>
            <div style="padding:12px 22px 18px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeCgInviteModal()"
                        style="padding:8px 18px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;background:#fff;cursor:pointer;">{{ __('admin.cancel') }}</button>
                <button type="submit"
                        style="padding:8px 22px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('admin.send_invite_email') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const ADMIN_CG_STR = {
    addFail:    '{{ __("admin.add_fail") }}',
    removeFail: '{{ __("admin.remove_fail") }}',
    roleFail:   '??븷 蹂寃쎌뿉 ?ㅽ뙣?덉뒿?덈떎.',
    removeConfirm: '{{ __("admin.remove_user_confirm") }}',
    noGroupUsers: '{{ __("admin.no_group_users") }}',
    roleAdmin:  '{{ __("admin.role_admin") }}',
    roleMember: '{{ __("admin.role_member") }}',
    roleClient: '{{ __("admin.role_client") }}',
    remove:     '{{ __("admin.remove") }}',
};

const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const GROUP_ID   = {{ $companyGroup->id }};
const ASSIGN_URL  = '{{ route('admin.company-groups.users.assign', $companyGroup) }}';
const REMOVE_BASE = '{{ url('admin/company-groups/' . $companyGroup->id . '/users') }}';
const SEARCH_URL  = '{{ route('admin.company-groups.users.search', $companyGroup) }}';

function buildRoleSelect(userId, role) {
    const sel = (v, lbl) => `<option value="${v}" ${role===v?'selected':''}>${lbl}</option>`;
    return `<select onchange="updateUserRole(${userId}, this)"
        style="padding:3px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#374151;background:#fff;outline:none;cursor:pointer;">
        ${sel('member', ADMIN_CG_STR.roleMember)}
        ${sel('admin',  ADMIN_CG_STR.roleAdmin)}
        ${sel('client', ADMIN_CG_STR.roleClient)}
    </select>`;
}

async function userAssign() {
    return {
        openModal: false,
        searchQ: '',
        searchResults: [],
        async doSearch() {
            if (this.searchQ.length < 1) { this.searchResults = []; return; }
            const r = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(this.searchQ));
            this.searchResults = await r.json();
        },
        async addUser(u) {
            const r = await fetch(ASSIGN_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ user_id: u.id }),
            });
            const data = await r.json();
            if (!data.ok) { alert(ADMIN_CG_STR.addFail); return; }

            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) emptyRow.remove();

            const tbody = document.getElementById('user-table-body');
            const row = document.createElement('tr');
            row.id = 'user-row-' + u.id;
            row.innerHTML = `
                <td style="font-weight:500;">${escHtml(data.user.name)}</td>
                <td style="color:#64748b;font-size:12px;">${escHtml(data.user.email)}</td>
                <td>${buildRoleSelect(u.id, data.user.role)}</td>
                <td><button type="button" onclick="removeUser(${u.id}, this)" style="font-size:11px;color:#ef4444;background:none;border:none;cursor:pointer;">${ADMIN_CG_STR.remove}</button></td>
            `;
            tbody.appendChild(row);

            this.searchResults = this.searchResults.filter(x => x.id !== u.id);
        },
    };
}

async function updateUserRole(userId, sel) {
    const prevVal = sel.dataset.prev !== undefined ? sel.dataset.prev : sel.options[sel.selectedIndex === 0 ? 1 : 0].value;
    sel.dataset.prev = sel.value;
    const r = await fetch(`${REMOVE_BASE}/${userId}/role`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ role: sel.value }),
    });
    const data = await r.json().catch(() => ({}));
    if (!data.ok) {
        alert(ADMIN_CG_STR.roleFail);
        sel.value = prevVal;
        sel.dataset.prev = prevVal;
    } else {
        showCgToast('??븷??蹂寃쎈릺?덉뒿?덈떎.');
    }
}

async function showCgToast(msg, ok = true) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;z-index:99999;color:#fff;background:${ok ? '#059669' : '#dc2626'};box-shadow:0 4px 12px rgba(0,0,0,.15);transition:opacity .3s;`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2200);
}

async function removeUser(userId, btn) {
    if (!await __confirm(ADMIN_CG_STR.removeConfirm)) return;
    const r = await fetch(REMOVE_BASE + '/' + userId + '/remove', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    if (r.ok) {
        document.getElementById('user-row-' + userId)?.remove();
        // 鍮꾩뼱?덉쑝硫??덈궡 硫붿떆吏 ?쒖떆
        const tbody = document.getElementById('user-table-body');
        if (tbody && tbody.children.length === 0) {
            tbody.innerHTML = `<tr id="empty-row"><td colspan="4" style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">${ADMIN_CG_STR.noGroupUsers}</td></tr>`;
        }
    } else {
        alert(ADMIN_CG_STR.removeFail);
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

const _cgInvModal = document.getElementById('cg-invite-modal');
async function openCgInviteModal() {
    _cgInvModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
async function closeCgInviteModal() {
    _cgInvModal.style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCgInviteModal(); });

@if(session('mail_error'))
openCgInviteModal();
@endif
</script>
@endsection

