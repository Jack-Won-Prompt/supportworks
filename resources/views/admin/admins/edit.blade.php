@extends('layouts.admin')

@section('title', $admin->name . ' ' . __('admin.edit'))

@section('header-actions')
<a href="{{ route('admin.admins.index') }}" class="btn-secondary">{{ __('admin.back_to_list') }}</a>
@endsection

@section('content')
<div style="max-width:640px;">
    <div class="admin-card">
        @if($admin->role === 'super_admin')
        <div class="alert-error" style="margin-bottom:16px;">{{ __('admin.super_admin_readonly') }}</div>
        @else
        <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
            @csrf @method('PATCH')

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.name_label') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $admin->name) }}" required
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('name')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.login_id_required_label') }}</label>
                    <input type="text" value="{{ $admin->login_id }}" disabled
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;background:#f8fafc;color:#64748b;box-sizing:border-box;">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} *</label>
                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                @error('email')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">
                    휴대폰 <span style="font-weight:400;color:#94a3b8;">(선택 · 문의/알림 SMS 수신용)</span>
                </label>
                <input type="tel" name="phone" value="{{ old('phone', $admin->phone) }}" placeholder="010-0000-0000" maxlength="20"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                @error('phone')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.role_label') }}</label>
                    <select name="role"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                        <option value="admin"         {{ old('role', $admin->role) === 'admin'         ? 'selected':'' }}>{{ __('admin.role_admin_label') }}</option>
                        <option value="operator"      {{ old('role', $admin->role) === 'operator'      ? 'selected':'' }}>{{ __('admin.role_operator_label') }}</option>
                        <option value="support_agent" {{ old('role', $admin->role) === 'support_agent' ? 'selected':'' }}>{{ __('admin.role_support_label') }}</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.status_label') }}</label>
                    <select name="status"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                        <option value="active"   {{ old('status', $admin->status) === 'active'   ? 'selected':'' }}>{{ __('admin.status_option_active') }}</option>
                        <option value="inactive" {{ old('status', $admin->status) === 'inactive' ? 'selected':'' }}>{{ __('admin.status_option_inactive') }}</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_new_label') }}</label>
                    <input type="password" name="password" minlength="8"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('password')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_confirm_label') }}</label>
                    <input type="password" name="password_confirmation"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
            </div>

            {{-- 담당 회사 배정 --}}
            @if($groups->isNotEmpty())
            <div style="border-top:1px solid #f1f5f9;padding-top:16px;margin-bottom:20px;">
                <h4 style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">{{ __('admin.assign_company') }}</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach($groups as $group)
                    @php $checked = in_array($group->id, old('group_ids', $assignedIds)); @endphp
                    <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid {{ $checked ? '#c7d2fe' : '#e2e8f0' }};border-radius:8px;cursor:pointer;background:{{ $checked ? '#eef2ff' : '#fff' }};" id="grp-lbl-{{ $group->id }}">
                        <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                            {{ $checked ? 'checked' : '' }}
                            style="width:15px;height:15px;accent-color:#6366f1;"
                            onchange="var l=this.closest('label');l.style.background=this.checked?'#eef2ff':'#fff';l.style.borderColor=this.checked?'#c7d2fe':'#e2e8f0'">
                        <span style="font-size:12px;font-weight:500;color:#334155;">{{ $group->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div style="display:flex;gap:12px;padding-top:8px;border-top:1px solid #f1f5f9;">
                <button type="submit" class="btn-primary">{{ __('admin.save') }}</button>
                <a href="{{ route('admin.admins.index') }}" class="btn-secondary">{{ __('admin.cancel') }}</a>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
