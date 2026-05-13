<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SupportWorks 가입하기</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #f5f3ff; }
        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%; padding: 13px 44px 13px 46px;
            border: 1.5px solid #e5e7eb; border-radius: 12px;
            font-size: 14px; color: #1e1b2e; background: #fff;
            outline: none; transition: border-color .15s, box-shadow .15s;
            box-sizing: border-box; font-family: inherit;
        }
        .input-wrap input:focus {
            border-color: #c4b5fd;
            box-shadow: 0 0 0 3px rgba(196,181,253,.15);
        }
        .input-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #c4b5fd; pointer-events: none;
        }
        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #d1d5db; cursor: pointer; background: none; border: none; padding: 2px;
            transition: color .15s;
        }
        .toggle-pw:hover { color: #9b8afb; }
        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #9b8afb 0%, #7c3aed 100%);
            color: #fff; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: opacity .15s, transform .15s;
            box-shadow: 0 6px 20px rgba(124,58,237,.3);
        }
        .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .strength-bar { height: 4px; border-radius: 2px; transition: width .3s, background .3s; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-12">

<div style="width:100%;max-width:460px;">

    {{-- 로고 --}}
    <div style="text-align:center;margin-bottom:32px;">
        <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,#c4b5fd,#9b8afb);border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;color:#fff;box-shadow:0 4px 14px rgba(155,138,251,.4);">S</div>
            <span style="font-size:20px;font-weight:800;color:#1e1b4b;letter-spacing:-.5px;">SupportWorks</span>
        </a>
    </div>

    {{-- 카드 --}}
    <div style="background:#fff;border-radius:20px;border:1px solid #ede9fe;box-shadow:0 8px 40px rgba(139,122,240,.1);overflow:hidden;">

        {{-- 상단 배너 --}}
        <div style="background:linear-gradient(135deg,#7c3aed,#9b8afb,#6ee7b7);padding:32px 32px 40px;text-align:center;position:relative;">
            <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:28px;backdrop-filter:blur(8px);">
                🎉
            </div>
            <h1 style="font-size:22px;font-weight:900;color:#fff;margin:0 0 6px;letter-spacing:-.5px;">SupportWorks에 가입하기</h1>
            <p style="font-size:13px;color:rgba(255,255,255,.75);margin:0;">공유 받은 파일에서 한 발 더 — 함께 협업해 보세요</p>
        </div>

        {{-- 초대한 사람 (공유자) --}}
        <div style="margin:-18px 28px 0;background:#fff;border:1.5px solid #ede9fe;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;box-shadow:0 4px 16px rgba(139,122,240,.1);position:relative;z-index:1;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#c4b5fd,#9b8afb);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;flex-shrink:0;">
                {{ mb_substr($inviterName, 0, 1) }}
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:10px;color:#a5b4fc;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">초대한 사람</div>
                <div style="font-size:14px;font-weight:700;color:#1e1b4b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $inviterName }}</div>
            </div>
        </div>

        {{-- 회사 소속(공유자의 회사) — 자동 설정 안내 --}}
        @if($companyName)
        <div style="margin:12px 28px 0;padding:11px 16px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#bbf7d0,#86efac);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" fill="none" stroke="#15803d" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:10px;color:#16a34a;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">소속 회사 (자동)</div>
                <div style="font-size:13px;font-weight:700;color:#14532d;">{{ $companyName }}</div>
            </div>
            <span style="flex-shrink:0;font-size:10px;padding:3px 8px;background:#dcfce7;color:#16a34a;border-radius:5px;font-weight:700;">기본 설정</span>
        </div>
        @endif

        {{-- 공유 파일 정보 --}}
        <div style="margin:12px 28px 0;padding:11px 16px;background:#faf5ff;border:1.5px solid #ede9fe;border-radius:12px;display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:10px;color:#a5b4fc;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">공유받은 파일</div>
                <div style="font-size:13px;font-weight:700;color:#1e1b4b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $file->original_name }}</div>
            </div>
        </div>

        {{-- 폼 --}}
        <div style="padding:28px 28px 32px;">

            @if($errors->any())
            <div style="margin-bottom:16px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('files.public-share.signup.post', $token) }}">
                @csrf

                <div style="display:flex;flex-direction:column;gap:18px;">

                    {{-- 이메일 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            이메일 <span style="color:#9b8afb;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com" maxlength="255">
                        </div>
                    </div>

                    {{-- 이름 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            이름 <span style="color:#9b8afb;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="홍길동" maxlength="100" style="padding-right:14px;">
                        </div>
                    </div>

                    {{-- 휴대폰 (선택) --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            휴대폰 <span style="font-weight:500;color:#9ca3af;font-size:11px;">(선택 · 알림 SMS 수신용)</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="010-0000-0000" maxlength="20" style="padding-right:14px;">
                        </div>
                    </div>

                    {{-- 비밀번호 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            비밀번호 <span style="color:#9b8afb;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input type="password" name="password" id="pw" required minlength="8" placeholder="8자 이상" oninput="checkStrength(this.value)">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw','eye1')">
                                <svg id="eye1" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div style="margin-top:8px;background:#f3f4f6;border-radius:4px;height:4px;overflow:hidden;">
                            <div id="strength-bar" class="strength-bar" style="width:0%;background:#ef4444;"></div>
                        </div>
                        <div id="strength-label" style="font-size:11px;color:#9ca3af;margin-top:4px;"></div>
                    </div>

                    {{-- 비밀번호 확인 --}}
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;">
                            비밀번호 확인 <span style="color:#9b8afb;">*</span>
                        </label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <input type="password" name="password_confirmation" id="pw2" required minlength="8" placeholder="다시 입력" oninput="checkMatch()">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw2','eye2')">
                                <svg id="eye2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div id="match-label" style="font-size:11px;margin-top:4px;"></div>
                    </div>

                    <button type="submit" class="btn-submit" style="margin-top:6px;">SupportWorks 가입하기</button>
                </div>
            </form>

            <p style="text-align:center;font-size:12px;color:#6b7280;margin-top:18px;margin-bottom:0;">
                이미 계정이 있으신가요? <a href="{{ route('login') }}" style="color:#7c3aed;font-weight:600;text-decoration:none;">로그인</a>
            </p>

            <p style="text-align:center;margin-top:14px;margin-bottom:0;">
                <a href="{{ route('files.public-share', $token) }}" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#9ca3af;text-decoration:none;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    공유 파일로 돌아가기
                </a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePw(id, eyeId) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
function checkStrength(v) {
    const bar = document.getElementById('strength-bar');
    const lbl = document.getElementById('strength-label');
    let score = 0;
    if (v.length >= 8)  score++;
    if (/[A-Z]/.test(v) || /[a-z]/.test(v) && /[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    if (v.length >= 12) score++;
    const widths  = ['0%','30%','55%','80%','100%'];
    const colors  = ['#ef4444','#ef4444','#f59e0b','#84cc16','#16a34a'];
    const labels  = ['','너무 짧음','보통','좋음','매우 강함'];
    bar.style.width = widths[score];
    bar.style.background = colors[score];
    lbl.textContent = v ? labels[score] : '';
    lbl.style.color = colors[score];
    checkMatch();
}
function checkMatch() {
    const a = document.getElementById('pw').value;
    const b = document.getElementById('pw2').value;
    const lbl = document.getElementById('match-label');
    if (!b) { lbl.textContent = ''; return; }
    if (a === b) { lbl.textContent = '✓ 일치합니다'; lbl.style.color = '#16a34a'; }
    else         { lbl.textContent = '✗ 일치하지 않습니다'; lbl.style.color = '#ef4444'; }
}
</script>

</body>
</html>
