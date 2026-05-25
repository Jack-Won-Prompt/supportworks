<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.login_title') }} — SupportWorks</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{font-family:'Inter','Noto Sans KR',sans-serif;background:#ffffff;color:#0f172a;display:flex;min-height:100vh;overflow-x:hidden}

        /* ── LEFT PANEL ── */
        .left-panel{
            position:relative;width:50%;display:flex;flex-direction:column;
            justify-content:space-between;padding:3rem;overflow:hidden;
            background:linear-gradient(150deg,#f8fafc 0%,#eef2ff 60%,#e0e7ff 100%);
            border-right:1px solid #e2e8f0;
        }
        #left-canvas{position:absolute;inset:0;z-index:0}
        .lp-glow1{position:absolute;top:-160px;left:-160px;width:520px;height:520px;border-radius:50%;
            background:radial-gradient(circle,rgba(99,102,241,.18) 0%,transparent 70%);pointer-events:none;z-index:0}
        .lp-glow2{position:absolute;bottom:-160px;right:-160px;width:420px;height:420px;border-radius:50%;
            background:radial-gradient(circle,rgba(6,182,212,.12) 0%,transparent 70%);pointer-events:none;z-index:0}

        .lp-logo{position:relative;z-index:1;display:flex;align-items:center;gap:.75rem}
        .lp-logo-icon{width:40px;height:40px;border-radius:12px;
            background:linear-gradient(135deg,#6366f1,#4f46e5);
            display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;
            box-shadow:0 8px 20px rgba(79,70,229,.25)}
        .lp-logo-text{font-size:1.1rem;font-weight:900;color:#0f172a;letter-spacing:-.3px}

        .lp-center{position:relative;z-index:1;flex:1;display:flex;flex-direction:row;align-items:center;gap:2.5rem;padding:2rem 0;overflow:hidden}
        .lp-text-col{flex:1;min-width:0}
        .lp-visual-col{flex-shrink:0;width:262px;display:flex;flex-direction:column;gap:.7rem}

        .lp-badge{display:inline-flex;align-items:center;gap:.5rem;
            background:#ffffff;border:1px solid #e0e7ff;
            border-radius:999px;padding:.35rem .95rem;font-size:.75rem;font-weight:600;color:#4338ca;
            margin-bottom:1.5rem;width:fit-content;
            box-shadow:0 1px 2px rgba(15,23,42,.04)}
        .lp-badge-dot{width:6px;height:6px;border-radius:50%;background:#6366f1;animation:blink 1.6s infinite}
        @keyframes blink{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,.55)}70%{box-shadow:0 0 0 6px rgba(99,102,241,0)}}
        .lp-title{font-size:clamp(1.6rem,2.8vw,2.4rem);font-weight:900;letter-spacing:-1.5px;line-height:1.15;margin-bottom:1rem;color:#0f172a}
        .lp-title .grad{background:linear-gradient(90deg,#4f46e5,#0891b2);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .lp-desc{font-size:.87rem;color:#475569;line-height:1.7;margin-bottom:0}

        .lp-card{
            background:#ffffff;
            border:1px solid #e2e8f0;border-radius:16px;
            padding:1.15rem;
            box-shadow:0 8px 24px -8px rgba(15,23,42,.08),0 2px 4px rgba(15,23,42,.03);
            animation:cardFloat 5s ease-in-out infinite;
        }
        .lp-mini-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:.9rem;box-shadow:0 4px 12px -4px rgba(15,23,42,.06)}
        .lp-mini-top{display:flex;align-items:center;gap:.45rem;margin-bottom:.6rem}
        .lp-mini-label{font-size:.71rem;font-weight:700;color:#64748b;flex:1;letter-spacing:.3px}
        .lp-ai-pulse{width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0;animation:aiPulse 2s ease-in-out infinite}
        @keyframes aiPulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.55)}60%{box-shadow:0 0 0 5px rgba(16,185,129,0)}}
        .lp-online-badge{font-size:.6rem;font-weight:700;color:#047857;background:#d1fae5;border:1px solid #a7f3d0;border-radius:6px;padding:.15rem .45rem}
        .lp-chips{display:flex;flex-wrap:wrap;gap:.3rem}
        .lp-chip{font-size:.61rem;font-weight:600;color:#4338ca;background:#eef2ff;border:1px solid #e0e7ff;border-radius:6px;padding:.18rem .45rem}
        .lp-notif-count{font-size:.6rem;font-weight:700;color:#c2410c;background:#ffedd5;border:1px solid #fed7aa;border-radius:6px;padding:.15rem .45rem}
        .lp-notif-item{display:flex;align-items:flex-start;gap:.45rem;margin-bottom:.4rem}
        .lp-notif-item:last-child{margin-bottom:0}
        .lp-notif-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0;margin-top:.25rem}
        .lp-notif-text{font-size:.67rem;color:#64748b;line-height:1.4}
        @keyframes cardFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .lp-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
        .lp-card-title{font-size:.8rem;font-weight:700;color:#475569;letter-spacing:.5px;text-transform:uppercase}
        .lp-card-badge{display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:600;color:#059669}
        .lp-progress-item{margin-bottom:.9rem}
        .lp-progress-item:last-child{margin-bottom:0}
        .lp-prog-row{display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:.4rem}
        .lp-prog-name{color:#475569}
        .lp-prog-val{color:#0f172a;font-weight:600}
        .lp-prog-bar{height:5px;background:#f1f5f9;border-radius:999px;overflow:hidden}
        .lp-prog-fill{height:100%;border-radius:999px}
        .lp-stats{display:flex;gap:.75rem;margin-top:1.25rem}
        .lp-stat{flex:1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:.75rem;text-align:center}
        .lp-stat-num{font-size:1.2rem;font-weight:900;background:linear-gradient(90deg,#4f46e5,#0891b2);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .lp-stat-lbl{font-size:.65rem;color:#94a3b8;margin-top:2px}
        .lp-features{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:.75rem}
        .lp-feat{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;color:#475569}
        .lp-feat::before{content:'✓';color:#10b981;font-weight:700}

        /* ── RIGHT PANEL ── */
        .right-panel{
            width:50%;display:flex;align-items:center;justify-content:center;
            padding:3rem 2rem;
            background:#ffffff;
        }
        .form-box{width:100%;max-width:400px}
        .mobile-logo{display:none;align-items:center;gap:.65rem;justify-content:center;margin-bottom:2.5rem}
        .mobile-logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff}
        .mobile-logo-text{font-size:1rem;font-weight:900;color:#0f172a;letter-spacing:-.3px}

        .form-head{margin-bottom:2rem}
        .form-head h2{font-size:1.75rem;font-weight:900;letter-spacing:-1px;margin-bottom:.4rem;color:#0f172a}
        .form-head p{font-size:.875rem;color:#64748b}

        .alert{display:flex;align-items:center;gap:.75rem;padding:.9rem 1rem;border-radius:12px;font-size:.82rem;margin-bottom:1.25rem}
        .alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
        .alert-info{display:block;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;padding:.95rem 1rem;border-radius:12px;font-size:.82rem;margin-bottom:1.25rem}
        .alert-info-title{display:flex;align-items:center;gap:.55rem;font-weight:700;margin-bottom:.35rem;color:#1d4ed8}
        .alert-info-desc{color:#1e3a8a;line-height:1.55;margin-bottom:.75rem}
        .alert-info-cta{display:inline-flex;align-items:center;gap:.4rem;background:#1d4ed8;color:#fff;border-radius:8px;padding:.5rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;transition:background .15s}
        .alert-info-cta:hover{background:#1e40af}

        .form-group{margin-bottom:1.1rem}
        .form-label{display:block;font-size:.8rem;font-weight:600;color:#334155;margin-bottom:.5rem;letter-spacing:.2px}
        .input-wrap{position:relative}
        .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;display:flex}
        .form-input{
            width:100%;padding:.85rem 1rem .85rem 2.75rem;
            background:#ffffff;
            border:1px solid #e2e8f0;
            border-radius:12px;font-size:.875rem;color:#0f172a;
            outline:none;transition:all .2s;font-family:inherit;
        }
        .form-input::placeholder{color:#cbd5e1}
        .form-input:focus{border-color:#6366f1;background:#ffffff;box-shadow:0 0 0 4px rgba(99,102,241,.12);}
        .input-action{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);
            background:none;border:none;color:#94a3b8;cursor:pointer;padding:.2rem;transition:color .2s;display:flex}
        .input-action:hover{color:#475569}

        .toggle-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem}
        .toggle-track{width:38px;height:21px;border-radius:999px;background:#e2e8f0;position:relative;cursor:pointer;flex-shrink:0;transition:background .25s;border:1px solid #cbd5e1}
        .toggle-track.on{background:linear-gradient(135deg,#6366f1,#4f46e5);border-color:transparent}
        .toggle-thumb{position:absolute;top:2px;left:2px;width:15px;height:15px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.2);transition:transform .25s}
        .toggle-track.on .toggle-thumb{transform:translateX(17px)}
        .toggle-lbl{font-size:.82rem;color:#475569;cursor:pointer;user-select:none}

        .btn-submit{
            width:100%;padding:.95rem;border:none;border-radius:12px;
            background:linear-gradient(135deg,#6366f1,#4f46e5);
            color:#fff;font-size:.925rem;font-weight:700;font-family:inherit;
            cursor:pointer;transition:all .25s;
            box-shadow:0 10px 24px -8px rgba(79,70,229,.5),0 2px 4px rgba(15,23,42,.06);
            position:relative;overflow:hidden;
        }
        .btn-submit::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
            animation:shimmer 2.4s infinite}
        @keyframes shimmer{0%{left:-100%}100%{left:160%}}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 14px 30px -8px rgba(79,70,229,.6),0 4px 8px rgba(15,23,42,.08)}
        .btn-inner{display:flex;align-items:center;justify-content:center;gap:.5rem}

        .divider{display:flex;align-items:center;gap:.9rem;margin:1.25rem 0}
        .divider-line{flex:1;height:1px;background:#e2e8f0}
        .divider-text{font-size:.75rem;color:#94a3b8}

        .btn-secondary{
            display:flex;align-items:center;justify-content:center;gap:.5rem;
            width:100%;padding:.85rem;border-radius:12px;
            background:#ffffff;border:1px solid #e2e8f0;
            color:#475569;font-size:.875rem;font-weight:600;font-family:inherit;
            text-decoration:none;cursor:pointer;transition:all .2s;
        }
        .btn-secondary:hover{background:#eef2ff;border-color:#c7d2fe;color:#4338ca}
        .btn-secondary .accent{color:#4f46e5}

        .forgot-link{display:block;text-align:right;font-size:.78rem;color:#64748b;text-decoration:none;margin-top:.5rem;transition:color .2s}
        .forgot-link:hover{color:#4f46e5}

        .form-footer{text-align:center;font-size:.75rem;color:#94a3b8;margin-top:2.5rem}

        /* lang switcher */
        .lang-switcher{position:absolute;top:1.5rem;right:1.5rem;display:flex;gap:4px;z-index:10}
        .lang-btn{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid #e2e8f0;background:#ffffff;color:#64748b;cursor:pointer;font-family:inherit;transition:all .15s}
        .lang-btn.active{background:#eef2ff;border-color:#c7d2fe;color:#4338ca}
        .lang-btn:hover{color:#0f172a;border-color:#cbd5e1}

        @media(max-width:900px){
            .left-panel{display:none}
            .right-panel{width:100%}
            .mobile-logo{display:flex}
        }
        @media(max-width:480px){
            .right-panel{padding:2rem 1.25rem}
        }
    </style>
</head>
<body>

<!-- 언어 전환 -->
<div class="lang-switcher">
    <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
        @csrf
        <input type="hidden" name="locale" value="ko">
        <button type="submit" class="lang-btn {{ app()->getLocale() === 'ko' ? 'active' : '' }}">KO</button>
    </form>
    <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
        @csrf
        <input type="hidden" name="locale" value="en">
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
        <div class="lp-text-col">
            <div class="lp-badge">
                <span class="lp-badge-dot"></span>
                {{ __('auth.badge') }}
            </div>
            <h2 class="lp-title">
                {!! __('auth.welcome') !!} <span class="grad">{{ __('auth.welcome_emoji') }}</span>
            </h2>
            <p class="lp-desc">{{ __('auth.welcome_desc') }}</p>
        </div>

        <div class="lp-visual-col">
            <div class="lp-card">
                <div class="lp-card-header">
                    <span class="lp-card-title">{{ __('app.nav_my_projects') }}</span>
                    <span class="lp-card-badge">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="4"/></svg>
                        ↑ 12%
                    </span>
                </div>
                <div class="lp-progress-item">
                    <div class="lp-prog-row"><span class="lp-prog-name">{{ __('auth.login_project1') }}</span><span class="lp-prog-val">68%</span></div>
                    <div class="lp-prog-bar"><div class="lp-prog-fill" style="width:68%;background:linear-gradient(90deg,#6366f1,#06b6d4)"></div></div>
                </div>
                <div class="lp-progress-item">
                    <div class="lp-prog-row"><span class="lp-prog-name">{{ __('auth.login_project2') }}</span><span class="lp-prog-val">41%</span></div>
                    <div class="lp-prog-bar"><div class="lp-prog-fill" style="width:41%;background:linear-gradient(90deg,#ec4899,#6366f1)"></div></div>
                </div>
                <div class="lp-progress-item">
                    <div class="lp-prog-row"><span class="lp-prog-name">{{ __('auth.login_project3') }}</span><span class="lp-prog-val">100%</span></div>
                    <div class="lp-prog-bar"><div class="lp-prog-fill" style="width:100%;background:linear-gradient(90deg,#10b981,#06b6d4)"></div></div>
                </div>
                <div class="lp-stats">
                    <div class="lp-stat"><div class="lp-stat-num">3</div><div class="lp-stat-lbl">Q&A</div></div>
                    <div class="lp-stat"><div class="lp-stat-num">12</div><div class="lp-stat-lbl">{{ __('auth.login_this_month') }}</div></div>
                    <div class="lp-stat"><div class="lp-stat-num">28</div><div class="lp-stat-lbl">{{ __('auth.login_files') }}</div></div>
                </div>
            </div>

            <div class="lp-mini-card">
                <div class="lp-mini-top">
                    <div class="lp-ai-pulse"></div>
                    <span class="lp-mini-label">{{ __('auth.feat_ai') }}</span>
                    <span class="lp-online-badge">{{ __('auth.login_online') }}</span>
                </div>
                <div class="lp-chips">
                    <span class="lp-chip">{{ __('auth.login_code_review') }}</span>
                    <span class="lp-chip">{{ __('auth.login_doc_summary') }}</span>
                    <span class="lp-chip">{{ __('auth.login_schedule') }}</span>
                    <span class="lp-chip">Q&A</span>
                </div>
            </div>

            <div class="lp-mini-card">
                <div class="lp-mini-top">
                    <span class="lp-mini-label">{{ __('auth.login_notifications') }}</span>
                    <span class="lp-notif-count">3 {{ __('auth.login_new') }}</span>
                </div>
                <div class="lp-notif-item">
                    <div class="lp-notif-dot" style="background:#6366f1"></div>
                    <span class="lp-notif-text">{{ __('auth.login_notif1') }}</span>
                </div>
                <div class="lp-notif-item">
                    <div class="lp-notif-dot" style="background:#06b6d4"></div>
                    <span class="lp-notif-text">{{ __('auth.login_notif2') }}</span>
                </div>
                <div class="lp-notif-item">
                    <div class="lp-notif-dot" style="background:#10b981"></div>
                    <span class="lp-notif-text">{{ __('auth.login_notif3') }}</span>
                </div>
            </div>
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
            <h2>{{ __('auth.login_title') }}</h2>
            <p>{{ __('auth.login_subtitle') }}</p>
        </div>

        @if(session('status'))
        <div class="alert alert-success">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('status') }}
        </div>
        @endif

        @if(session('pending_invite'))
        <div class="alert alert-info">
            <div class="alert-info-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('auth.pending_invite_title') }}
            </div>
            <div class="alert-info-desc">{{ __('auth.pending_invite_desc', ['email' => session('pending_invite.email')]) }}</div>
            <a href="{{ session('pending_invite.url') }}" class="alert-info-cta">
                {{ __('auth.pending_invite_cta') }}
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-error">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            {{ __('auth.error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">{{ __('auth.email') }}</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="form-input" placeholder="your@email.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">{{ __('auth.password') }}</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <input id="password" type="password" name="password" required
                           class="form-input" placeholder="••••••••">
                    <button type="button" class="input-action" onclick="togglePw()" id="pw-toggle">
                        <svg id="pw-eye" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">{{ __('auth.forgot_link') }}</a>
                @endif
            </div>

            <div class="toggle-row">
                <div class="toggle-track on" id="toggle" onclick="toggleRemember()">
                    <div class="toggle-thumb"></div>
                </div>
                <input type="checkbox" name="remember" id="remember" style="display:none" checked>
                <label class="toggle-lbl" onclick="toggleRemember()">{{ __('auth.remember') }}</label>
            </div>

            <button type="submit" class="btn-submit">
                <span class="btn-inner">
                    {{ __('auth.login_btn') }}
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </span>
            </button>
        </form>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">{{ __('auth.or') }}</span>
            <div class="divider-line"></div>
        </div>

        <a href="{{ route('register') }}" class="btn-secondary">
            {{ __('auth.no_account') }} &nbsp;<span class="accent">{{ __('auth.signup') }}</span>
        </a>

        <p class="form-footer">{{ str_replace(':year', date('Y'), __('auth.footer')) }}</p>
    </div>
</div>

<script>
const canvas = document.getElementById('left-canvas');
const ctx = canvas.getContext('2d');
let W, H, particles = [];
function resize() { W = canvas.width = canvas.offsetWidth; H = canvas.height = canvas.offsetHeight; }
resize();
window.addEventListener('resize', resize);
class Particle {
    constructor() { this.reset(); }
    reset() {
        this.x = Math.random()*W; this.y = Math.random()*H;
        this.vx=(Math.random()-.5)*.3; this.vy=(Math.random()-.5)*.3;
        this.r=Math.random()*1.2+.4; this.alpha=Math.random()*.35+.1;
        this.color=['#6366f1','#06b6d4','#10b981','#818cf8'][Math.floor(Math.random()*4)];
    }
    update(){ this.x+=this.vx; this.y+=this.vy; if(this.x<0||this.x>W||this.y<0||this.y>H) this.reset(); }
    draw(){ ctx.beginPath(); ctx.arc(this.x,this.y,this.r,0,Math.PI*2); ctx.fillStyle=this.color; ctx.globalAlpha=this.alpha; ctx.fill(); }
}
for(let i=0;i<80;i++) particles.push(new Particle());
function drawConnections(){
    for(let i=0;i<particles.length;i++) for(let j=i+1;j<particles.length;j++){
        const dx=particles[i].x-particles[j].x, dy=particles[i].y-particles[j].y;
        const dist=Math.sqrt(dx*dx+dy*dy);
        if(dist<80){ ctx.beginPath(); ctx.moveTo(particles[i].x,particles[i].y); ctx.lineTo(particles[j].x,particles[j].y); ctx.strokeStyle='#6366f1'; ctx.globalAlpha=(1-dist/80)*.12; ctx.lineWidth=.5; ctx.stroke(); }
    }
}
function animate(){ ctx.clearRect(0,0,W,H); ctx.globalAlpha=1; drawConnections(); particles.forEach(p=>{p.update();p.draw();}); ctx.globalAlpha=1; requestAnimationFrame(animate); }
animate();

function togglePw() {
    const input = document.getElementById('password');
    const eye = document.getElementById('pw-eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;
    } else {
        input.type = 'password';
        eye.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    }
}
function toggleRemember() {
    const cb = document.getElementById('remember');
    const track = document.getElementById('toggle');
    cb.checked = !cb.checked;
    track.classList.toggle('on', cb.checked);
}
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', () => { input.closest('.input-wrap').querySelector('.input-icon').style.color = '#6366f1'; });
    input.addEventListener('blur',  () => { input.closest('.input-wrap').querySelector('.input-icon').style.color = ''; });
});
</script>
</body>
</html>
