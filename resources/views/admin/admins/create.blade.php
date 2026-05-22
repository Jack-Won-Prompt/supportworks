@extends('layouts.admin')

@section('title', __('admin.create_account'))

@section('header-actions')
<a href="{{ route('admin.admins.index') }}" class="btn-secondary">{{ __('admin.back_to_list') }}</a>
@endsection

@section('content')
<div style="max-width:640px;">
    <div class="admin-card">
        <form method="POST" action="{{ route('admin.admins.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.name_label') }} *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('name')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.login_id_required_label') }} *</label>
                    <input type="text" name="login_id" id="login_id_input" value="{{ old('login_id') }}" required
                        placeholder="{{ __('admin.login_id_hint_valid') }}"
                        pattern="[A-Za-z0-9_]+"
                        title="{{ __('admin.login_id_hint_valid') }}"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'"
                        oninput="validateLoginId(this)">
                    <p id="login_id_hint" style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ __('admin.login_id_hint_valid') }}</p>
                    @error('login_id')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.col_email') }} *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                @error('email')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">
                    휴대폰 <span style="font-weight:400;color:#94a3b8;">(선택 · 문의/알림 SMS 수신용)</span>
                </label>
                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="010-0000-0000" maxlength="20"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                @error('phone')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_label') }} *</label>
                    <input type="password" name="password" required minlength="8"
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('password')<p style="font-size:11px;color:#ef4444;margin-top:3px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.password_confirm_label') }} *</label>
                    <input type="password" name="password_confirmation" required
                        style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('admin.role_label') }} *</label>
                <select name="role"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    <option value="admin"         {{ old('role') === 'admin'         ? 'selected' : '' }}>{{ __('admin.role_option_admin') }}</option>
                    <option value="operator"      {{ old('role') === 'operator'      ? 'selected' : '' }}>{{ __('admin.role_option_operator') }}</option>
                    <option value="support_agent" {{ old('role') === 'support_agent' ? 'selected' : '' }}>{{ __('admin.role_option_support_agent') }}</option>
                </select>
            </div>

            {{-- 담당 회사 그룹 --}}
            @if($groups->isNotEmpty())
            <div style="border-top:1px solid #f1f5f9;padding-top:16px;margin-bottom:20px;">
                <h4 style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;">{{ __('admin.assign_company') }}</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach($groups as $group)
                    @php $checked = in_array($group->id, (array) old('group_ids', [])); @endphp
                    <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid {{ $checked ? '#c7d2fe' : '#e2e8f0' }};border-radius:8px;cursor:pointer;background:{{ $checked ? '#eef2ff' : '#fff' }};">
                        <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                            {{ $checked ? 'checked' : '' }}
                            style="width:15px;height:15px;accent-color:#6366f1;"
                            onchange="this.closest('label').style.background = this.checked ? '#eef2ff' : '#fff'; this.closest('label').style.borderColor = this.checked ? '#c7d2fe' : '#e2e8f0'">
                        <span style="font-size:12px;font-weight:500;color:#334155;">{{ $group->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div style="display:flex;gap:12px;padding-top:8px;border-top:1px solid #f1f5f9;">
                <button type="submit" class="btn-primary">{{ __('admin.create_account') }}</button>
                <a href="{{ route('admin.admins.index') }}" class="btn-secondary">{{ __('admin.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const ADMIN_CREATE_STR = {
    hintValid:    '{{ __("admin.login_id_hint_valid") }}',
    hintInvalid:  '{{ __("admin.login_id_invalid_chars") }}',
    hintOk:       '{{ __("admin.login_id_valid_format") }}',
};

function validateLoginId(input) {
    const hint = document.getElementById('login_id_hint');
    const val = input.value;
    if (!val) {
        hint.textContent = ADMIN_CREATE_STR.hintValid;
        hint.style.color = '#94a3b8';
        input.style.borderColor = '#e2e8f0';
        return;
    }
    if (/[^A-Za-z0-9_]/.test(val)) {
        hint.textContent = ADMIN_CREATE_STR.hintInvalid;
        hint.style.color = '#ef4444';
        input.style.borderColor = '#ef4444';
    } else {
        hint.textContent = ADMIN_CREATE_STR.hintOk;
        hint.style.color = '#10b981';
        input.style.borderColor = '#10b981';
    }
}
</script>
@endsection
