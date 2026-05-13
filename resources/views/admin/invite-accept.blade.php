<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SupportWorks {{ __('admin.invite_setup_sub') }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.09); padding: 40px; width: 100%; max-width: 440px; }
.logo { text-align: center; margin-bottom: 32px; }
.logo-title { font-size: 22px; font-weight: 700; color: #18181b; }
.logo-sub { font-size: 13px; color: #71717a; margin-top: 4px; }
.invite-info { background: #faf8ff; border: 1px solid #ede9fe; border-radius: 10px; padding: 16px 20px; margin-bottom: 28px; }
.invite-info .label { font-size: 11px; color: #a1a1aa; margin-bottom: 8px; font-weight: 600; letter-spacing: .3px; text-transform: uppercase; }
.invite-info .name { font-size: 16px; font-weight: 700; color: #18181b; margin-bottom: 4px; }
.invite-info .email { font-size: 13px; color: #52525b; margin-bottom: 8px; }
.role-badge { display: inline-block; background: #ede9fe; color: #5b3fe0; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.form-group { margin-bottom: 18px; }
label { display: block; font-size: 13px; font-weight: 500; color: #3f3f46; margin-bottom: 6px; }
input { width: 100%; padding: 10px 14px; border: 1.5px solid #e4e4e7; border-radius: 8px; font-size: 14px; color: #18181b; outline: none; transition: border-color .15s; }
input:focus { border-color: #6d4aff; }
.hint { font-size: 11.5px; color: #a1a1aa; margin-top: 5px; }
.error { font-size: 11.5px; color: #ef4444; margin-top: 5px; }
.btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #6d4aff, #5b3fe0); color: #fff; border: none; border-radius: 9px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; transition: opacity .15s; }
.btn:hover { opacity: .92; }
.expires { text-align: center; font-size: 12px; color: #a1a1aa; margin-top: 20px; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-title">SupportWorks</div>
        <div class="logo-sub">{{ __('admin.invite_setup_sub') }}</div>
    </div>

    <div class="invite-info">
        <div class="label">{{ __('admin.invite_info_label') }}</div>
        <div class="name">{{ $invitation->name }}</div>
        <div class="email">{{ $invitation->email }}</div>
        <span class="role-badge">
            {{ ['admin' => __('admin.role_admin_label'), 'operator' => __('admin.role_operator_label'), 'support_agent' => __('admin.role_support_label')][$invitation->role] ?? $invitation->role }}
        </span>
    </div>

    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#dc2626;">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ url('/admin/invite/accept/' . $token) }}">
        @csrf

        <div class="form-group">
            <label for="login_id">{{ __('admin.login_id_field') }}</label>
            <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}"
                   placeholder="{{ __('admin.login_id_field_ph') }}" autocomplete="username">
            <div class="hint">{{ __('admin.login_id_hint') }}</div>
            @error('login_id') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="password">{{ __('admin.password_label') }}</label>
            <input type="password" id="password" name="password" placeholder="{{ __('admin.password_min_ph') }}" autocomplete="new-password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">{{ __('admin.password_confirm_label') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="{{ __('admin.password_confirm_ph') }}" autocomplete="new-password">
        </div>

        <button type="submit" class="btn">{{ __('admin.create_account_btn') }}</button>
    </form>

    <div class="expires">
        {{ __('admin.invite_expires', ['date' => $invitation->expires_at->format('Y년 m월 d일 H:i')]) }}
    </div>
</div>
</body>
</html>
