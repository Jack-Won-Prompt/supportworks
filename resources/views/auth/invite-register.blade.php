<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.invite_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f8fafc; }
        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%; padding: 13px 44px 13px 46px;
            border: 1.5px solid #e5e7eb; border-radius: 12px;
            font-size: 14px; color: #1e1b2e; background: #fff;
            outline: none; transition: border-color .15s, box-shadow .15s;
            box-sizing: border-box;
        }
        .input-wrap input:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(196,181,253,.15);
        }
        .input-wrap input.readonly-input {
            background: #f9f7ff; color: #6b7280; cursor: not-allowed;
        }
        .input-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #a5b4fc; pointer-events: none;
        }
        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #d1d5db; cursor: pointer; background: none; border: none; padding: 2px;
            transition: color .15s;
        }
        .toggle-pw:hover { color: #818cf8; }
        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #818cf8 0%, #4f46e5 100%);
            color: #fff; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: opacity .15s, transform .15s;
            box-shadow: 0 6px 20px rgba(79,70,229,.3);
        }
        .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .strength-bar { height: 4px; border-radius: 2px; transition: width .3s, background .3s; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-12">

<div style="width: 100%; max-width: 460px;">

    {{-- 로고 --}}
    <div style="text-align:center; margin-bottom: 32px;">
        <a href="{{ url('/') }}" style="display:inline-flex; align-items:center; gap:12px; text-decoration:none;">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,#a5b4fc,#818cf8);border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;color:#fff;box-shadow:0 4px 14px rgba(99,102,241,.4);">S</div>
            <span style="font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-.5px;">SupportWorks</span>
        </a>
    </div>

    {{-- 카드 --}}
    <div style="background:#fff;border-radius:20px;border:1px solid #e0e7ff;box-shadow:0 8px 40px rgba(99,102,241,.1);overflow:hidden;">

        {{-- 상단 배너 --}}
        <div style="background:linear-gradient(135deg,#4f46e5,#818cf8,#6ee7b7);padding:32px 32px 40px;text-align:center;position:relative;">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:28px;backdrop-filter:blur(8px);">
                🎉
            </div>
            <h1 style="font-size:22px;font-weight:900;color:#fff;margin:0 0 6px;letter-spacing:-.5px;">{{ __('auth.invite_banner_title') }}</h1>
            <p style="font-size:13px;color:rgba(255,255,255,.75);margin:0;">{{ __('auth.invite_banner_sub') }}</p>
        </div>

        {{-- 이메일 배지 --}}
        <div style="margin:-18px 28px 0;background:#fff;border:1.5px solid #e0e7ff;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 4px 16px rgba(99,102,241,.1);position:relative;z-index:1;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#e0e7ff,#c7d2fe);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" fill="none" stroke="#818cf8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;color:#6366f1;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">{{ __('auth.invite_email_label') }}</div>
                <div style="font-size:14px;font-weight:700;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $invitation->email }}</div>
            </div>
            <div style="flex-shrink:0;margin-left:auto;">
                <span style="font-size:10px;padding:3px 8px;background:#dcfce7;color:#16a34a;border-radius:5px;font-weight:700;">{{ __('auth.invite_email_verified') }}</span>
            </div>
        </div>

        {{-- 초대자 정보 --}}
        @if($inviterName)
        <div style="margin:16px 28px 0;padding:11px 16px;background:#eef2ff;border:1.5px solid #e0e7ff;border-radius:12px;display:flex;align-items:center;gap:12px;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#a5b4fc,#818cf8);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ mb_substr($inviterName, 0, 1) }}
            </div>
            <div>
                <div style="font-size:10px;color:#6366f1;font-weight:600;letter-spacing:.4px;text-transform:uppercase;">초대한 사람</div>
                <div style="font-size:13px;font-weight:700;color:#0f172a;">{{ $inviterName }}</div>
            </div>
        </div>
        @endif

        {{-- 초대 메시지 --}}
        @if($invitation->message)
        <div style="margin:12px 28px 0;padding:12px 16px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px;">
                <svg width="13" height="13" fill="none" stroke="#d97706" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span style="font-size:11px;font-weight:700;color:#d97706;letter-spacing:.3px;">초대 메시지</span>
            </div>
            <p style="font-size:13px;color:#92400e;line-height:1.6;margin:0;white-space:pre-wrap;">{{ $invitation->message }}</p>
        </div>
        @endif

        {{-- 참여 예정 프로젝트 --}}
        @if($invitedProjects->isNotEmpty())
        <div style="margin:12px 28px 0;padding:12px 16px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span style="font-size:11px;font-weight:700;color:#16a34a;letter-spacing:.3px;">{{ __('auth.invite_projects_label') }}</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($invitedProjects as $project)
                <div style="background:#fff;border:1px solid #bbf7d0;border-radius:8px;padding:9px 12px;">
                    <div style="font-size:13px;font-weight:700;color:#15803d;">{{ $project->name }}</div>
                    @if($project->description)
                    <div style="font-size:11px;color:#6b7280;margin-top:3px;line-height:1.5;">{{ Str::limit($project->description, 80) }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- 폼 --}}
        <div style="padding:28px 28px 32px;">

            @if($errors->any())
            <div style="margin-bottom:16px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('team.register', $invitation->token) }}" id="invite-form">
                @csrf

                <div style="display:flex;flex-direction:column;gap:20px;">

                    {{-- 이름 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            {{ __('auth.invite_label_name') }} <span style="color:#818cf8;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <input type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="{{ __('auth.invite_placeholder_name') }}"
                                style="padding-right:14px;">
                        </div>
                    </div>

                    {{-- 휴대폰 (선택) --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            휴대폰 <span style="font-weight:500;color:#9ca3af;font-size:11px;">(선택 · 승인 알림 SMS 수신용)</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <input type="tel" name="phone" value="{{ old('phone', $invitation->phone) }}" placeholder="010-0000-0000" maxlength="20" style="padding-right:14px;">
                        </div>
                    </div>

                    {{-- 비밀번호 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            {{ __('auth.invite_label_password') }} <span style="color:#818cf8;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input type="password" name="password" id="pw" required placeholder="{{ __('auth.invite_placeholder_pw') }}" oninput="checkStrength(this.value)">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw','eye1')">
                                <svg id="eye1" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        {{-- 강도 바 --}}
                        <div style="margin-top:8px;background:#f3f4f6;border-radius:4px;height:4px;overflow:hidden;">
                            <div id="strength-bar" class="strength-bar" style="width:0%;background:#ef4444;"></div>
                        </div>
                        <div id="strength-label" style="font-size:11px;color:#9ca3af;margin-top:4px;"></div>
                    </div>

                    {{-- 비밀번호 확인 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            {{ __('auth.invite_label_pw_confirm') }} <span style="color:#818cf8;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <input type="password" name="password_confirmation" id="pw2" required placeholder="{{ __('auth.invite_placeholder_pw2') }}" oninput="checkMatch()">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw2','eye2')">
                                <svg id="eye2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <div id="match-label" style="font-size:11px;margin-top:4px;"></div>
                    </div>

                    {{-- 제출 버튼 --}}
                    <button type="submit" id="submit-btn" class="btn-submit" style="margin-top:6px;">
                        {{ __('auth.invite_submit') }}
                    </button>

                </div>
            </form>

        </div>
    </div>

    {{-- 하단 텍스트 --}}
    <p style="text-align:center;font-size:12px;color:#a5b4fc;margin-top:20px;">
        © {{ date('Y') }} SupportWorks. All rights reserved.
    </p>

</div>

<script>
// ── i18n strings for JS ──
var INVITE_STR = {
    strength_0: '{{ __('auth.invite_strength_0') }}',
    strength_1: '{{ __('auth.invite_strength_1') }}',
    strength_2: '{{ __('auth.invite_strength_2') }}',
    strength_3: '{{ __('auth.invite_strength_3') }}',
    strength_4: '{{ __('auth.invite_strength_4') }}',
    pw_match:   '{{ __('auth.invite_pw_match') }}',
    pw_mismatch:'{{ __('auth.invite_pw_mismatch') }}',
    processing: '{{ __('auth.invite_processing') }}',
};

function togglePw(inputId, iconId) {
    var input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function checkStrength(val) {
    var bar = document.getElementById('strength-bar');
    var lbl = document.getElementById('strength-label');
    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        { w: '0%',   c: '#ef4444', t: INVITE_STR.strength_0 },
        { w: '25%',  c: '#ef4444', t: INVITE_STR.strength_1 },
        { w: '50%',  c: '#f59e0b', t: INVITE_STR.strength_2 },
        { w: '75%',  c: '#3b82f6', t: INVITE_STR.strength_3 },
        { w: '100%', c: '#10b981', t: INVITE_STR.strength_4 },
    ];
    var lv = levels[score];
    bar.style.width = lv.w;
    bar.style.background = lv.c;
    lbl.textContent = lv.t;
    lbl.style.color = lv.c;
}

function checkMatch() {
    var pw  = document.getElementById('pw').value;
    var pw2 = document.getElementById('pw2').value;
    var lbl = document.getElementById('match-label');
    if (!pw2) { lbl.textContent = ''; return; }
    if (pw === pw2) {
        lbl.textContent = INVITE_STR.pw_match;
        lbl.style.color = '#10b981';
    } else {
        lbl.textContent = INVITE_STR.pw_mismatch;
        lbl.style.color = '#ef4444';
    }
}

document.getElementById('invite-form').addEventListener('submit', function(e) {
    var btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = INVITE_STR.processing;
});
</script>

</body>
</html>
