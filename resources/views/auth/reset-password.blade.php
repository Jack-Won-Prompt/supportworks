<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.reset_form_title') }} — SupportWorks</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{font-family:'Inter','Noto Sans KR',sans-serif;background:#0f0a1e;color:#fff;display:flex;min-height:100vh;overflow-x:hidden}

        /* ── LEFT PANEL ── */
        .left-panel{
            position:relative;width:50%;display:flex;flex-direction:column;
            justify-content:space-between;padding:3rem;overflow:hidden;
            background:linear-gradient(150deg,#0f0a1e 0%,#1a0a4e 50%,#0d1a3a 100%);
        }
        #left-canvas{position:absolute;inset:0;z-index:0}
        .lp-glow1{position:absolute;top:-150px;left:-150px;width:500px;height:500px;border-radius:50%;
            background:radial-gradient(circle,rgba(109,40,217,.4) 0%,transparent 70%);pointer-events:none;z-index:0}
        .lp-glow2{position:absolute;bottom:-150px;right:-150px;width:400px;height:400px;border-radius:50%;
            background:radial-gradient(circle,rgba(6,182,212,.25) 0%,transparent 70%);pointer-events:none;z-index:0}

        .lp-logo{position:relative;z-index:1;display:flex;align-items:center;gap:.75rem}
        .lp-logo-icon{width:40px;height:40px;border-radius:12px;
            background:linear-gradient(135deg,#8b5cf6,#6d28d9);
            display:flex;align-items:center;justify-content:center;font-size:1.1rem;
            box-shadow:0 0 20px rgba(139,92,246,.5)}
        .lp-logo-text{font-size:1.1rem;font-weight:900;
            background:linear-gradient(90deg,#a78bfa,#67e8f9);
            -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}

        .lp-center{position:relative;z-index:1;flex:1;display:flex;flex-direction:column;justify-content:center;padding:2rem 0}

        .lp-badge{display:inline-flex;align-items:center;gap:.5rem;
            background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.35);
            border-radius:999px;padding:.3rem .9rem;font-size:.75rem;font-weight:600;color:#c4b5fd;
            margin-bottom:1.5rem;width:fit-content}
        .lp-badge-dot{width:6px;height:6px;border-radius:50%;background:#a78bfa;animation:blink 1.6s infinite}
        @keyframes blink{0%,100%{box-shadow:0 0 0 0 rgba(167,139,250,.8)}70%{box-shadow:0 0 0 6px rgba(167,139,250,0)}}

        .lp-title{font-size:clamp(1.6rem,2.8vw,2.4rem);font-weight:900;letter-spacing:-1.5px;line-height:1.15;margin-bottom:1rem}
        .lp-title .grad{background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .lp-desc{font-size:.87rem;color:rgba(255,255,255,.45);line-height:1.7;margin-bottom:2rem}

        .lp-tips{display:flex;flex-direction:column;gap:.8rem}
        .lp-tip{display:flex;align-items:flex-start;gap:.85rem;
            background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);
            border-radius:12px;padding:.85rem 1rem}
        .lp-tip-icon{width:30px;height:30px;border-radius:9px;flex-shrink:0;
            display:flex;align-items:center;justify-content:center}
        .lp-tip-body{}
        .lp-tip-title{font-size:.78rem;font-weight:700;color:rgba(255,255,255,.7);margin-bottom:.2rem}
        .lp-tip-desc{font-size:.71rem;color:rgba(255,255,255,.35);line-height:1.5}

        .lp-strength-demo{position:relative;z-index:1;
            background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
            border-radius:14px;padding:1.1rem 1.2rem;margin-top:1.5rem}
        .lp-sd-label{font-size:.72rem;font-weight:600;color:rgba(255,255,255,.4);margin-bottom:.75rem;letter-spacing:.4px;text-transform:uppercase}
        .lp-sd-row{display:flex;align-items:center;gap:.65rem;margin-bottom:.45rem}
        .lp-sd-row:last-child{margin-bottom:0}
        .lp-sd-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
        .lp-sd-text{font-size:.72rem;color:rgba(255,255,255,.4)}

        .lp-features{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:.75rem}
        .lp-feat{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;color:rgba(255,255,255,.5)}
        .lp-feat::before{content:'✓';color:#34d399;font-weight:700}

        /* ── RIGHT PANEL ── */
        .right-panel{
            width:50%;display:flex;align-items:center;justify-content:center;
            padding:3rem 2rem;
            background:rgba(255,255,255,.015);border-left:1px solid rgba(255,255,255,.06);
        }
        .form-box{width:100%;max-width:400px}

        .mobile-logo{display:none;align-items:center;gap:.65rem;justify-content:center;margin-bottom:2.5rem}
        .mobile-logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);display:flex;align-items:center;justify-content:center;font-size:1rem}
        .mobile-logo-text{font-size:1rem;font-weight:900;background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}

        .form-head{margin-bottom:2rem}
        .form-icon{width:52px;height:52px;border-radius:16px;
            background:linear-gradient(135deg,rgba(139,92,246,.25),rgba(109,40,217,.15));
            border:1px solid rgba(139,92,246,.3);
            display:flex;align-items:center;justify-content:center;
            margin-bottom:1.25rem;color:#a78bfa}
        .form-head h2{font-size:1.75rem;font-weight:900;letter-spacing:-1px;margin-bottom:.4rem;color:#f0eeff}
        .form-head p{font-size:.875rem;color:rgba(255,255,255,.4);line-height:1.6}

        .alert{display:flex;align-items:flex-start;gap:.75rem;padding:.9rem 1rem;border-radius:12px;font-size:.82rem;margin-bottom:1.5rem;line-height:1.5}
        .alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
        .alert-error .alert-icon{color:#f87171;flex-shrink:0;margin-top:1px}

        .form-group{margin-bottom:1.1rem}
        .form-label{display:block;font-size:.8rem;font-weight:600;color:rgba(255,255,255,.6);margin-bottom:.5rem;letter-spacing:.2px}
        .input-wrap{position:relative}
        .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);pointer-events:none;display:flex}
        .form-input{
            width:100%;padding:.85rem 2.75rem .85rem 2.75rem;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.1);
            border-radius:12px;font-size:.875rem;color:#f0eeff;
            outline:none;transition:all .2s;font-family:inherit;
        }
        .form-input::placeholder{color:rgba(255,255,255,.25)}
        .form-input:focus{
            border-color:rgba(139,92,246,.6);
            background:rgba(139,92,246,.08);
            box-shadow:0 0 0 3px rgba(139,92,246,.15);
        }
        .form-input.email-input{padding-right:1rem}
        .input-action{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);
            background:none;border:none;color:rgba(255,255,255,.3);cursor:pointer;padding:.2rem;transition:color .2s;display:flex}
        .input-action:hover{color:rgba(255,255,255,.7)}

        /* strength bar */
        .strength-bar-wrap{margin-top:.5rem}
        .strength-bars{display:flex;gap:3px;margin-bottom:.3rem}
        .strength-seg{height:3px;flex:1;border-radius:999px;background:rgba(255,255,255,.1);transition:background .3s}
        .strength-label{font-size:.72rem;color:rgba(255,255,255,.35);transition:color .3s}

        /* match indicator */
        .match-hint{font-size:.72rem;margin-top:.4rem;display:none}
        .match-hint.ok{color:#34d399;display:block}
        .match-hint.ng{color:#f87171;display:block}

        .btn-submit{
            width:100%;padding:.95rem;border:none;border-radius:12px;
            background:linear-gradient(135deg,#8b5cf6,#6d28d9);
            color:#fff;font-size:.925rem;font-weight:700;font-family:inherit;
            cursor:pointer;transition:all .25s;
            box-shadow:0 0 25px rgba(139,92,246,.4),0 4px 15px rgba(0,0,0,.3);
            position:relative;overflow:hidden;margin-top:.4rem;
        }
        .btn-submit::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent);
            animation:shimmer 2.4s infinite}
        @keyframes shimmer{0%{left:-100%}100%{left:160%}}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 0 40px rgba(139,92,246,.6),0 8px 25px rgba(0,0,0,.4)}
        .btn-submit:disabled{opacity:.55;cursor:not-allowed;transform:none}
        .btn-inner{display:flex;align-items:center;justify-content:center;gap:.5rem}

        .back-link{
            display:flex;align-items:center;justify-content:center;gap:.45rem;
            width:100%;padding:.8rem;border-radius:12px;margin-top:.9rem;
            background:transparent;border:1px solid rgba(255,255,255,.08);
            color:rgba(255,255,255,.4);font-size:.85rem;font-weight:500;font-family:inherit;
            text-decoration:none;cursor:pointer;transition:all .2s;
        }
        .back-link:hover{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.15);color:rgba(255,255,255,.7)}

        .form-footer{text-align:center;font-size:.75rem;color:rgba(255,255,255,.2);margin-top:2.5rem}

        @keyframes spin{to{transform:rotate(360deg)}}

        @media(max-width:900px){
            .left-panel{display:none}
            .right-panel{width:100%;border-left:none}
            .mobile-logo{display:flex}
        }
        @media(max-width:480px){
            .right-panel{padding:2rem 1.25rem}
        }

        /* ── Lang switcher ── */
        .lang-switcher{position:fixed;top:1.25rem;right:1.5rem;z-index:100;display:flex;gap:.3rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:.2rem}
        .lang-btn{background:none;border:none;color:rgba(255,255,255,.45);font-size:.72rem;font-weight:700;font-family:inherit;padding:.28rem .5rem;border-radius:5px;cursor:pointer;transition:all .2s;letter-spacing:.5px}
        .lang-btn:hover{color:#fff;background:rgba(255,255,255,.08)}
        .lang-btn.active{color:#a78bfa;background:rgba(139,92,246,.2)}
    </style>
</head>
<body>

<!-- Language Switcher -->
<div class="lang-switcher">
    <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
        @csrf <input type="hidden" name="locale" value="ko">
        <button type="submit" class="lang-btn {{ app()->getLocale() === 'ko' ? 'active' : '' }}">KO</button>
    </form>
    <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
        @csrf <input type="hidden" name="locale" value="en">
        <button type="submit" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
    </form>
</div>

<!-- LEFT PANEL -->
<div class="left-panel">
    <canvas id="left-canvas"></canvas>
    <div class="lp-glow1"></div>
    <div class="lp-glow2"></div>

    <div class="lp-logo">
        <div class="lp-logo-icon">⚡</div>
        <span class="lp-logo-text">SupportWorks</span>
    </div>

    <div class="lp-center">
        <div class="lp-badge">
            <span class="lp-badge-dot"></span>
            {{ __('auth.reset_badge') }}
        </div>
        <h2 class="lp-title">
            {!! __('auth.reset_headline') !!}
        </h2>
        <p class="lp-desc">{{ __('auth.reset_desc') }}</p>

        <div class="lp-tips">
            <div class="lp-tip">
                <div class="lp-tip-icon" style="background:rgba(139,92,246,.15)">
                    <svg width="15" height="15" fill="none" stroke="#a78bfa" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <div class="lp-tip-body">
                    <div class="lp-tip-title">{{ __('auth.reset_tip1_title') }}</div>
                    <div class="lp-tip-desc">{{ __('auth.reset_tip1_desc') }}</div>
                </div>
            </div>
            <div class="lp-tip">
                <div class="lp-tip-icon" style="background:rgba(6,182,212,.12)">
                    <svg width="15" height="15" fill="none" stroke="#67e8f9" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </div>
                <div class="lp-tip-body">
                    <div class="lp-tip-title">{{ __('auth.reset_tip2_title') }}</div>
                    <div class="lp-tip-desc">{{ __('auth.reset_tip2_desc') }}</div>
                </div>
            </div>
            <div class="lp-tip">
                <div class="lp-tip-icon" style="background:rgba(52,211,153,.1)">
                    <svg width="15" height="15" fill="none" stroke="#34d399" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="lp-tip-body">
                    <div class="lp-tip-title">{{ __('auth.reset_tip3_title') }}</div>
                    <div class="lp-tip-desc">{{ __('auth.reset_tip3_desc') }}</div>
                </div>
            </div>
        </div>

        <div class="lp-strength-demo">
            <div class="lp-sd-label">{{ __('auth.reset_strength_label') }}</div>
            <div class="lp-sd-row"><div class="lp-sd-dot" style="background:#f87171"></div><span class="lp-sd-text">{{ __('auth.reset_s_very_weak') }}</span></div>
            <div class="lp-sd-row"><div class="lp-sd-dot" style="background:#fb923c"></div><span class="lp-sd-text">{{ __('auth.reset_s_weak') }}</span></div>
            <div class="lp-sd-row"><div class="lp-sd-dot" style="background:#facc15"></div><span class="lp-sd-text">{{ __('auth.reset_s_medium') }}</span></div>
            <div class="lp-sd-row"><div class="lp-sd-dot" style="background:#34d399"></div><span class="lp-sd-text">{{ __('auth.reset_s_strong') }}</span></div>
        </div>
    </div>

    <div class="lp-features">
        <div class="lp-feat">{{ __('auth.feat_projects') }}</div>
        <div class="lp-feat">{{ __('auth.feat_chat') }}</div>
        <div class="lp-feat">{{ __('auth.feat_ai') }}</div>
        <div class="lp-feat">{{ __('auth.feat_files') }}</div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
    <div class="form-box">

        <div class="mobile-logo">
            <div class="mobile-logo-icon">⚡</div>
            <span class="mobile-logo-text">SupportWorks</span>
        </div>

        <div class="form-head">
            <div class="form-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2>{{ __('auth.reset_form_title') }}</h2>
            <p>{!! __('auth.reset_form_sub') !!}</p>
        </div>

        @if($errors->any())
        <div class="alert alert-error">
            <div class="alert-icon">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            </div>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" id="reset-form">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">{{ __('auth.email') }}</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input id="email" type="email" name="email"
                           value="{{ old('email', $request->email) }}"
                           required autofocus autocomplete="username"
                           class="form-input email-input"
                           placeholder="your@email.com">
                </div>
            </div>

            <!-- New Password -->
            <div class="form-group">
                <label class="form-label" for="password">{{ __('auth.reset_new_pw') }}</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input id="password" type="password" name="password"
                           required autocomplete="new-password"
                           class="form-input" placeholder="••••••••"
                           oninput="checkStrength(this.value); checkMatch()">
                    <button type="button" class="input-action" onclick="togglePw('password','eye1')">
                        <svg id="eye1" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <div class="strength-bar-wrap">
                    <div class="strength-bars">
                        <div class="strength-seg" id="s1"></div>
                        <div class="strength-seg" id="s2"></div>
                        <div class="strength-seg" id="s3"></div>
                        <div class="strength-seg" id="s4"></div>
                    </div>
                    <div class="strength-label" id="strength-label"></div>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label" for="password_confirmation">{{ __('auth.reset_confirm_pw') }}</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           required autocomplete="new-password"
                           class="form-input" placeholder="••••••••"
                           oninput="checkMatch()">
                    <button type="button" class="input-action" onclick="togglePw('password_confirmation','eye2')">
                        <svg id="eye2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <div class="match-hint" id="match-hint"></div>
            </div>

            <button type="submit" class="btn-submit" id="submit-btn">
                <span class="btn-inner" id="btn-text">
                    {{ __('auth.reset_submit') }}
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </button>
        </form>

        <a href="{{ route('login') }}" class="back-link">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('auth.reset_back') }}
        </a>

        <p class="form-footer">{{ str_replace(':year', date('Y'), __('auth.footer')) }}</p>
    </div>
</div>

<script>
const RESET_STR = {
    veryWeak:   '{{ __("auth.strength_very_weak") }}',
    weak:       '{{ __("auth.strength_weak") }}',
    medium:     '{{ __("auth.strength_medium") }}',
    strong:     '{{ __("auth.strength_strong") }}',
    matchOk:    '{{ __("auth.reset_match_ok") }}',
    matchNg:    '{{ __("auth.reset_match_ng") }}',
    processing: '{{ __("auth.reset_processing") }}',
};
// ── Particle canvas ──
const canvas = document.getElementById('left-canvas');
if (canvas) {
    const ctx = canvas.getContext('2d');
    let W, H, particles = [];
    function resize() { W = canvas.width = canvas.offsetWidth; H = canvas.height = canvas.offsetHeight; }
    resize();
    window.addEventListener('resize', resize);
    class Particle {
        constructor() { this.reset(); }
        reset() {
            this.x = Math.random() * W; this.y = Math.random() * H;
            this.vx = (Math.random() - 0.5) * 0.3; this.vy = (Math.random() - 0.5) * 0.3;
            this.r = Math.random() * 1.2 + 0.4; this.alpha = Math.random() * 0.4 + 0.1;
            this.color = ['#8b5cf6','#06b6d4','#34d399','#a78bfa'][Math.floor(Math.random() * 4)];
        }
        update() { this.x += this.vx; this.y += this.vy; if (this.x < 0 || this.x > W || this.y < 0 || this.y > H) this.reset(); }
        draw() { ctx.beginPath(); ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2); ctx.fillStyle = this.color; ctx.globalAlpha = this.alpha; ctx.fill(); }
    }
    for (let i = 0; i < 80; i++) particles.push(new Particle());
    function drawConnections() {
        for (let i = 0; i < particles.length; i++) for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x, dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 80) { ctx.beginPath(); ctx.moveTo(particles[i].x, particles[i].y); ctx.lineTo(particles[j].x, particles[j].y); ctx.strokeStyle = '#8b5cf6'; ctx.globalAlpha = (1 - dist / 80) * 0.1; ctx.lineWidth = 0.5; ctx.stroke(); }
        }
    }
    function animate() { ctx.clearRect(0, 0, W, H); ctx.globalAlpha = 1; drawConnections(); particles.forEach(p => { p.update(); p.draw(); }); ctx.globalAlpha = 1; requestAnimationFrame(animate); }
    animate();
}

// ── Password strength ──
function checkStrength(val) {
    const segs = [document.getElementById('s1'), document.getElementById('s2'), document.getElementById('s3'), document.getElementById('s4')];
    const label = document.getElementById('strength-label');
    if (!val) { segs.forEach(s => s.style.background = ''); label.textContent = ''; return; }
    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { max:1, color:'#f87171', label:RESET_STR.veryWeak, segs:1 },
        { max:2, color:'#fb923c', label:RESET_STR.weak,     segs:2 },
        { max:3, color:'#facc15', label:RESET_STR.medium,   segs:3 },
        { max:5, color:'#34d399', label:RESET_STR.strong,   segs:4 },
    ];
    const lv = levels.find(l => score <= l.max) || levels[3];
    segs.forEach((s, i) => s.style.background = i < lv.segs ? lv.color : 'rgba(255,255,255,.1)');
    label.textContent = lv.label;
    label.style.color = lv.color;
}

// ── Password match ──
function checkMatch() {
    const pw = document.getElementById('password').value;
    const cf = document.getElementById('password_confirmation').value;
    const hint = document.getElementById('match-hint');
    if (!cf) { hint.className = 'match-hint'; return; }
    if (pw === cf) { hint.className = 'match-hint ok'; hint.textContent = RESET_STR.matchOk; }
    else           { hint.className = 'match-hint ng'; hint.textContent = RESET_STR.matchNg; }
}

// ── Toggle password visibility ──
const eyeOpen  = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
const eyeClosed = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;
function togglePw(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);
    if (input.type === 'password') { input.type = 'text'; eye.innerHTML = eyeClosed; }
    else { input.type = 'password'; eye.innerHTML = eyeOpen; }
}

// ── Submit loading state ──
const form = document.getElementById('reset-form');
const submitBtn = document.getElementById('submit-btn');
const btnText = document.getElementById('btn-text');
if (form) {
    form.addEventListener('submit', () => {
        if (submitBtn) {
            submitBtn.disabled = true;
            btnText.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin .8s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> ${RESET_STR.processing}`;
        }
    });
}

// ── Input focus glow ──
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', () => {
        const icon = input.closest('.input-wrap')?.querySelector('.input-icon');
        if (icon) icon.style.color = 'rgba(167,139,250,.7)';
    });
    input.addEventListener('blur', () => {
        const icon = input.closest('.input-wrap')?.querySelector('.input-icon');
        if (icon) icon.style.color = '';
    });
});
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
