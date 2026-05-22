@extends('layouts.admin')

@section('title', __('admin.user_manage'))

@section('header-actions')
    <button onclick="openCreateModal()" class="btn-primary">+ {{ __('admin.add_user') }}</button>
    <button onclick="openInviteModal()" class="btn-primary" style="background:#7c3aed;" onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">✉ {{ __('admin.invite_user') }}</button>
@endsection

@section('content')
<div class="pt-4">
    <form method="GET" class="flex gap-3 mb-5 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_name_email') }}"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
        <select name="role" onchange="this.form.submit()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">{{ __('admin.all_roles') }}</option>
            <option value="admin"   {{ request('role') === 'admin'   ? 'selected' : '' }}>{{ __('admin.role_admin') }}</option>
            <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>{{ __('admin.role_manager') }}</option>
            <option value="member"  {{ request('role') === 'member'  ? 'selected' : '' }}>{{ __('admin.role_member') }}</option>
            <option value="client"  {{ request('role') === 'client'  ? 'selected' : '' }}>{{ __('admin.role_client') }}</option>
        </select>
        @if($groups->isNotEmpty())
        <select name="group_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">{{ __('admin.all_company') }}</option>
            @foreach($groups as $group)
            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
            @endforeach
        </select>
        @endif
        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">{{ __('admin.search_btn') }}</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_name') }}</th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_email') }}</th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_role') }}</th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_company_group') }}</th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_company') }}</th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_joined') }}</th>
                    <th class="px-4 py-3.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $user->role === 'admin' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $user->role === 'member' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $user->role === 'client' ? 'bg-green-100 text-green-700' : '' }}">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-gray-500">{{ $user->email }}</td>
                    <td class="px-4 py-3.5">
                        <span class="px-2.5 py-1 text-xs font-medium rounded-full
                            {{ $user->role === 'admin' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $user->role === 'member' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $user->role === 'client' ? 'bg-green-100 text-green-700' : '' }}">
                            {{ $user->role_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5">
                        <select class="inline-group-select text-xs border border-indigo-100 bg-indigo-50 text-indigo-700 rounded-full px-2.5 py-0.5 cursor-pointer focus:outline-none focus:ring-1 focus:ring-indigo-400 hover:border-indigo-300 transition"
                                data-url="{{ route('admin.users.update-group', $user) }}"
                                data-original="{{ $user->company_group_id ?? '' }}"
                                style="max-width:160px;">
                            <option value="">{{ __('admin.unassigned') }}</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ $user->company_group_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-4 py-3.5 text-gray-500">{{ $user->company ?? '-' }}</td>
                    <td class="px-4 py-3.5 text-gray-500">{{ $user->created_at->format('Y.m.d') }}</td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            <button onclick="openEditModal(this)"
                                    data-url="{{ route('admin.users.update', $user) }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-company="{{ $user->company ?? '' }}"
                                    data-phone="{{ $user->phone ?? '' }}"
                                    data-group="{{ $user->company_group_id ?? '' }}"
                                    data-sr-agent="{{ $user->is_sr_agent ? 1 : 0 }}"
                                    data-project-ids="{{ $user->projects->pluck('id')->implode(',') }}"
                                    class="text-xs text-indigo-600 hover:text-indigo-700">{{ __('admin.usr_edit') }}</button>
                            <button onclick="impersonateUser('{{ route('admin.users.impersonate', $user) }}', '{{ addslashes($user->name) }}')"
                                    class="text-xs text-emerald-600 hover:text-emerald-700">{{ __('admin.usr_login_as_user') }}</button>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('{{ __('admin.usr_confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">{{ __('admin.usr_delete') }}</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $users->withQueryString()->links() }}</div>

    {{-- 대기 중인 초대 --}}
    @if($pendingInvitations->isNotEmpty())
    <div class="mt-8">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('admin.usr_pending_invites') }} <span class="text-gray-400 font-normal">({{ $pendingInvitations->count() }}건)</span></h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.col_email') }}</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.usr_col_affiliated_company') }}</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.usr_col_invite_date') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($pendingInvitations as $inv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $inv->email }}</td>
                        <td class="px-4 py-3">
                            @if($inv->companyGroup)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-violet-50 text-violet-600">{{ $inv->companyGroup->name }}</span>
                            @else
                            <span class="text-gray-400 text-xs">{{ __('admin.unassigned') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $inv->created_at->format('Y.m.d H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.users.invitations.cancel', $inv) }}"
                                  onsubmit="return confirm('{{ __('admin.usr_confirm_cancel_invite') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">{{ __('admin.usr_cancel') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- ── 사용자 추가 모달 ─────────────────────────────────────────── --}}
<div id="create-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" onclick="if(event.target===this)closeCreateModal()">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);" onclick="event.stopPropagation()">
        <div style="padding:20px 24px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;">
            <h3 style="font-size:16px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.add_user') }}</h3>
            <button onclick="closeCreateModal()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;line-height:1;">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" id="create-form">
            @csrf
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:12px;">

                @if($errors->createUser->any())
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#dc2626;">
                    @foreach($errors->createUser->all() as $err)
                    <div>{{ $err }}</div>
                    @endforeach
                </div>
                @endif

                {{-- 이름 / 이메일 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_name') }} <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="{{ __('admin.person_name_ph') }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="user@example.com"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                {{-- 비밀번호 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_label') }} <span style="color:#ef4444;">*</span></label>
                        <input type="password" name="password" required minlength="8"
                               placeholder="{{ __('admin.password_min_ph') }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_confirm_label') }} <span style="color:#ef4444;">*</span></label>
                        <input type="password" name="password_confirmation" required
                               placeholder="{{ __('admin.password_confirm_ph') }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                {{-- 역할 / 소속 회사 그룹 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_role') }} <span style="color:#ef4444;">*</span></label>
                        <select name="role" required
                                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                            <option value="client"  {{ old('role', 'client') === 'client'  ? 'selected' : '' }}>{{ __('admin.role_client') }}</option>
                            <option value="member"  {{ old('role') === 'member'  ? 'selected' : '' }}>{{ __('admin.role_member') }}</option>
                            <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>{{ __('admin.role_manager') }}</option>
                            <option value="admin"   {{ old('role') === 'admin'   ? 'selected' : '' }}>{{ __('admin.role_admin') }}</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_affiliated_group') }}</label>
                        <select name="company_group_id"
                                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                            <option value="">{{ __('admin.unassigned') }}</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('company_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- SR 담당자 --}}
                <div>
                    <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:13px;color:#475569;cursor:pointer;">
                        <input type="checkbox" name="is_sr_agent" value="1" {{ old('is_sr_agent') ? 'checked' : '' }}
                               style="width:16px;height:16px;accent-color:#6366f1;flex-shrink:0;">
                        <span style="font-weight:500;">SR 담당자</span>
                        <span style="font-size:11px;color:#94a3b8;margin-left:auto;">SR 관리에서 회사 필터 노출</span>
                    </label>
                </div>

                {{-- 회사 / 연락처 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_company') }}</label>
                        <input type="text" name="company" value="{{ old('company') }}"
                               placeholder="{{ __('admin.company_ph') }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_contact') }}</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               placeholder="010-0000-0000"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

            </div>
            <div style="padding:14px 24px 20px;display:flex;align-items:center;justify-content:flex-end;gap:12px;border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeCreateModal()"
                        style="padding:9px 20px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;background:#fff;cursor:pointer;">{{ __('admin.usr_cancel') }}</button>
                <button type="submit"
                        style="padding:9px 24px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    {{ __('admin.usr_create') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── 사용자 수정 모달 ─────────────────────────────────────────── --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" onclick="if(event.target===this)closeEditModal()">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);" onclick="event.stopPropagation()">
        <div style="padding:20px 24px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;">
            <h3 id="edit-modal-title" style="font-size:16px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.user_edit_title') }}</h3>
            <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;line-height:1;">✕</button>
        </div>
        <form id="edit-form" onsubmit="submitEditModal(event)">
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:12px;">

                <div id="edit-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#dc2626;white-space:pre-line;"></div>

                {{-- 이름 / 이메일 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_name') }} <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="edit-name" required
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
                        <input type="email" id="edit-email" required
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                {{-- 새 비밀번호 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_new_password') }} <span style="font-weight:400;color:#94a3b8;">{{ __('admin.usr_change_only') }}</span></label>
                        <input type="password" id="edit-password" minlength="8"
                               placeholder="{{ __('admin.password_min_ph') }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_confirm_label') }}</label>
                        <input type="password" id="edit-password-confirm"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                {{-- 역할 / 소속 회사 그룹 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_role') }} <span style="color:#ef4444;">*</span></label>
                        <select id="edit-role"
                                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                            <option value="client">{{ __('admin.role_client') }}</option>
                            <option value="member">{{ __('admin.role_member') }}</option>
                            <option value="manager">{{ __('admin.role_manager') }}</option>
                            <option value="admin">{{ __('admin.role_admin') }}</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_affiliated_group') }}</label>
                        <select id="edit-group"
                                style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                            <option value="">{{ __('admin.unassigned') }}</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- SR 담당자 --}}
                <div>
                    <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:13px;color:#475569;cursor:pointer;">
                        <input type="checkbox" id="edit-sr-agent" style="width:16px;height:16px;accent-color:#6366f1;flex-shrink:0;">
                        <span style="font-weight:500;">SR 담당자</span>
                        <span style="font-size:11px;color:#94a3b8;margin-left:auto;">SR 관리에서 회사 필터 노출</span>
                    </label>
                </div>

                {{-- 회사 / 연락처 --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_company') }}</label>
                        <input type="text" id="edit-company"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_contact') }}</label>
                        <input type="text" id="edit-phone"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                {{-- 프로젝트 배정 (멀티 선택) --}}
                @if($projects->isNotEmpty())
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;">{{ __('admin.mgmt_project_assign') }}</label>
                        <span id="edit-proj-count" style="font-size:11px;font-weight:500;color:#6366f1;"></span>
                    </div>
                    <div style="border:1px solid #e2e8f0;border-radius:8px;max-height:180px;overflow-y:auto;padding:8px 12px;display:flex;flex-direction:column;gap:4px;">
                        @foreach($projects as $project)
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                            <input type="checkbox" class="edit-proj-check" value="{{ $project->id }}"
                                   style="width:14px;height:14px;accent-color:#6366f1;">
                            <span style="flex:1;">{{ $project->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
            <div style="padding:14px 24px 20px;display:flex;align-items:center;justify-content:flex-end;gap:12px;border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeEditModal()"
                        style="padding:9px 20px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;background:#fff;cursor:pointer;">{{ __('admin.usr_cancel') }}</button>
                <button type="submit" id="edit-submit-btn"
                        style="padding:9px 24px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    {{ __('admin.usr_save') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── 사용자 초대 모달 ─────────────────────────────────────────── --}}
<div id="invite-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" onclick="if(event.target===this)closeInviteModal()">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;" onclick="event.stopPropagation()">
        <div style="padding:20px 24px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:16px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.invite_user') }}</h3>
            <button onclick="closeInviteModal()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;line-height:1;">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.users.invite') }}" id="invite-form">
            @csrf
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">

                @if(session('mail_error'))
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;font-size:13px;color:#dc2626;">
                    {{ session('mail_error') }}<br>
                    <a href="{{ session('invite_link') }}" target="_blank" style="color:#7c3aed;word-break:break-all;font-size:12px;">{{ session('invite_link') }}</a>
                </div>
                @endif

                @if($errors->default->any())
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#dc2626;">
                    @foreach($errors->default->all() as $err)
                    <div>{{ $err }}</div>
                    @endforeach
                </div>
                @endif

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" value="{{ old('email', session('invite_link_email')) }}" required
                           placeholder="invite@example.com"
                           style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_col_affiliated_company') }} <span style="color:#ef4444;">*</span></label>
                    <select name="company_group_id" required
                            style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                        <option value="">{{ __('admin.usr_company_select') }}</option>
                        @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ old('company_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($projects->isNotEmpty())
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_join_project') }} <span style="font-weight:400;color:#94a3b8;">{{ __('admin.usr_optional') }}</span></label>
                    <div style="border:1px solid #e2e8f0;border-radius:8px;max-height:140px;overflow-y:auto;padding:8px 12px;display:flex;flex-direction:column;gap:4px;">
                        @foreach($projects as $project)
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                            <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                   {{ in_array($project->id, old('project_ids', [])) ? 'checked' : '' }}
                                   style="width:14px;height:14px;accent-color:#7c3aed;">
                            {{ $project->name }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.usr_invite_msg') }} <span style="font-weight:400;color:#94a3b8;">{{ __('admin.usr_optional') }}</span></label>
                    <textarea name="message" rows="3" maxlength="500"
                              placeholder="{{ __('admin.usr_invite_msg_ph') }}"
                              style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;outline:none;box-sizing:border-box;"
                              onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">{{ old('message') }}</textarea>
                </div>

            </div>
            <div style="padding:14px 24px 20px;display:flex;align-items:center;justify-content:flex-end;gap:12px;border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeInviteModal()"
                        style="padding:9px 20px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;background:#fff;cursor:pointer;">{{ __('admin.usr_cancel') }}</button>
                <button type="submit"
                        style="padding:9px 24px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    {{ __('admin.usr_invite_email_send') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const ADMIN_B_STR = {
    change_fail:           '{{ __("admin.usr_change_fail") }}',
    request_fail:          '{{ __("admin.usr_request_fail") }}',
    update_fail:           '{{ __("admin.usr_update_fail") }}',
    fetch_fail:            '{{ __("admin.usr_fetch_fail") }}',
    saving:                '{{ __("admin.usr_saving") }}',
    save:                  '{{ __("admin.usr_save") }}',
    impersonate_error:     '{{ __("admin.usr_impersonate_error") }}',
    edit_suffix:           '{{ __("admin.usr_edit_title_suffix") }}',
    login_as_user:         '{{ __("admin.usr_login_as_user") }}',
    new_window_note:       '{{ __("admin.usr_new_window_note") }}',
};

// ── 인라인 회사 그룹 변경 ──────────────────────────
document.querySelectorAll('.inline-group-select').forEach(sel => {
    sel.addEventListener('change', async function () {
        const url      = this.dataset.url;
        const original = this.dataset.original;
        const value    = this.value;
        const csrf     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        this.disabled = true;
        try {
            const res  = await fetch(url, {
                method:  'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body:    JSON.stringify({ company_group_id: value || null }),
            });
            const data = await res.json();
            if (data.ok) {
                this.dataset.original = value;
                this.style.borderColor = '';
                const saved = document.createElement('span');
                saved.textContent = '✓';
                saved.style.cssText = 'color:#16a34a;font-size:11px;margin-left:4px;';
                this.parentNode.appendChild(saved);
                setTimeout(() => saved.remove(), 1500);
            } else {
                alert(data.message ?? ADMIN_B_STR.change_fail);
                this.value = original;
            }
        } catch {
            alert(ADMIN_B_STR.request_fail);
            this.value = original;
        } finally {
            this.disabled = false;
        }
    });
});

// ── 사용자 추가 모달 ──────────────────────────────
const _createModal = document.getElementById('create-modal');

async function openCreateModal() {
    _createModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

async function closeCreateModal() {
    _createModal.style.display = 'none';
    document.body.style.overflow = '';
}

// ── 사용자 초대 모달 ──────────────────────────────
const _inviteModal = document.getElementById('invite-modal');

async function openInviteModal() {
    _inviteModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

async function closeInviteModal() {
    _inviteModal.style.display = 'none';
    document.body.style.overflow = '';
}

// ── 사용자 수정 모달 ──────────────────────────────
const _editModal = document.getElementById('edit-modal');

async function openEditModal(btn) {
    const d = btn.dataset;
    document.getElementById('edit-form').dataset.url = d.url;
    document.getElementById('edit-modal-title').textContent = d.name + ' ' + ADMIN_B_STR.edit_suffix;
    document.getElementById('edit-name').value    = d.name;
    document.getElementById('edit-email').value   = d.email;
    document.getElementById('edit-role').value    = d.role;
    document.getElementById('edit-company').value = d.company;
    document.getElementById('edit-phone').value   = d.phone;
    document.getElementById('edit-group').value   = d.group;
    document.getElementById('edit-sr-agent').checked = d.srAgent === '1';
    document.getElementById('edit-password').value         = '';
    document.getElementById('edit-password-confirm').value = '';
    document.getElementById('edit-error').style.display    = 'none';

    const projIds = (d.projectIds || '').split(',').filter(Boolean).map(Number);
    document.querySelectorAll('.edit-proj-check').forEach(cb => {
        cb.checked = projIds.includes(parseInt(cb.value, 10));
    });
    updateEditProjCount();

    _editModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function updateEditProjCount() {
    const el = document.getElementById('edit-proj-count');
    if (!el) return;
    const n = document.querySelectorAll('.edit-proj-check:checked').length;
    el.textContent = n > 0 ? n + '개 선택됨' : '';
}
document.querySelectorAll('.edit-proj-check').forEach(cb => cb.addEventListener('change', updateEditProjCount));

async function closeEditModal() {
    _editModal.style.display = 'none';
    document.body.style.overflow = '';
}

async function submitEditModal(e) {
    e.preventDefault();
    const errEl  = document.getElementById('edit-error');
    const btn    = document.getElementById('edit-submit-btn');
    errEl.style.display = 'none';
    btn.disabled = true; btn.textContent = ADMIN_B_STR.saving;

    const pw = document.getElementById('edit-password').value;
    const projectIds = [...document.querySelectorAll('.edit-proj-check:checked')].map(cb => parseInt(cb.value, 10));
    const body = {
        name:                document.getElementById('edit-name').value,
        email:               document.getElementById('edit-email').value,
        role:                document.getElementById('edit-role').value,
        company:             document.getElementById('edit-company').value,
        phone:               document.getElementById('edit-phone').value,
        company_group_id:    document.getElementById('edit-group').value || null,
        is_sr_agent:         document.getElementById('edit-sr-agent').checked ? 1 : 0,
        project_ids:         projectIds,
        project_ids_present: 1,
    };
    if (pw) {
        body.password              = pw;
        body.password_confirmation = document.getElementById('edit-password-confirm').value;
    }

    try {
        const res = await fetch(document.getElementById('edit-form').dataset.url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(body),
        });
        const d = await res.json().catch(() => ({}));
        if (res.ok && d.ok) {
            closeEditModal();
            location.reload();
        } else {
            const msgs = d.errors
                ? Object.values(d.errors).flat().join('\n')
                : (d.message || ADMIN_B_STR.update_fail);
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        }
    } catch {
        errEl.textContent = ADMIN_B_STR.fetch_fail;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = ADMIN_B_STR.save;
    }
}

document.addEventListener('keydown', async function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
        closeEditModal();
        closeInviteModal();
    }
});

@if($errors->createUser->any())
openCreateModal();
@endif

@if($errors->default->any() || session('mail_error'))
openInviteModal();
@endif

async function impersonateUser(url, userName) {
    if (!await __confirm(`"${userName}" ${ADMIN_B_STR.login_as_user}?\n${ADMIN_B_STR.new_window_note}`)) return;
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.url) {
            window.open(data.url, '_blank');
        } else {
            alert(ADMIN_B_STR.impersonate_error);
        }
    } catch (e) {
        alert(ADMIN_B_STR.request_fail + ': ' + e.message);
    }
}
</script>
@endsection
