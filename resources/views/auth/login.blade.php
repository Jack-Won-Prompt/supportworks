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

        .lp-center{position:relative;z-index:1;flex:1;display:flex;flex-direction:row;align-items:center;gap:2.5rem;padding:2rem 0;overflow:hidden}
        .lp-text-col{flex:1;min-width:0}
        .lp-visual-col{flex-shrink:0;width:262px;display:flex;flex-direction:column;gap:.7rem}

        .lp-badge{display:inline-flex;align-items:center;gap:.5rem;
            background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.35);
            border-radius:999px;padding:.3rem .9rem;font-size:.75rem;font-weight:600;color:#c4b5fd;
            margin-bottom:1.5rem;width:fit-content}
        .lp-badge-dot{width:6px;height:6px;border-radius:50%;background:#a78bfa;animation:blink 1.6s infinite}
        @keyframes blink{0%,100%{box-shadow:0 0 0 0 rgba(167,139,250,.8)}70%{box-shadow:0 0 0 6px rgba(167,139,250,0)}}
        .lp-title{font-size:clamp(1.6rem,2.8vw,2.4rem);font-weight:900;letter-spacing:-1.5px;line-height:1.15;margin-bottom:1rem}
        .lp-title .grad{background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .lp-desc{font-size:.87rem;color:rgba(255,255,255,.45);line-height:1.7;margin-bottom:0}

        .lp-card{
            background:rgba(255,255,255,.05);backdrop-filter:blur(16px);
            border:1px solid rgba(255,255,255,.1);border-radius:16px;
            padding:1.15rem;
            animation:cardFloat 5s ease-in-out infinite;
        }
        .lp-mini-card{background:rgba(255,255,255,.04);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:.9rem}
        .lp-mini-top{display:flex;align-items:center;gap:.45rem;margin-bottom:.6rem}
        .lp-mini-label{font-size:.71rem;font-weight:700;color:rgba(255,255,255,.5);flex:1;letter-spacing:.3px}
        .lp-ai-pulse{width:7px;height:7px;border-radius:50%;background:#34d399;flex-shrink:0;animation:aiPulse 2s ease-in-out infinite}
        @keyframes aiPulse{0%,100%{box-shadow:0 0 0 0 rgba(52,211,153,.7)}60%{box-shadow:0 0 0 5px rgba(52,211,153,0)}}
        .lp-online-badge{font-size:.6rem;font-weight:700;color:#34d399;background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.25);border-radius:6px;padding:.15rem .45rem}
        .lp-chips{display:flex;flex-wrap:wrap;gap:.3rem}
        .lp-chip{font-size:.61rem;font-weight:600;color:rgba(167,139,250,.85);background:rgba(139,92,246,.12);border:1px solid rgba(139,92,246,.22);border-radius:6px;padding:.18rem .45rem}
        .lp-notif-count{font-size:.6rem;font-weight:700;color:#fb923c;background:rgba(251,146,60,.1);border:1px solid rgba(251,146,60,.22);border-radius:6px;padding:.15rem .45rem}
        .lp-notif-item{display:flex;align-items:flex-start;gap:.45rem;margin-bottom:.4rem}
        .lp-notif-item:last-child{margin-bottom:0}
        .lp-notif-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0;margin-top:.25rem}
        .lp-notif-text{font-size:.67rem;color:rgba(255,255,255,.38);line-height:1.4}
        @keyframes cardFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .lp-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
        .lp-card-title{font-size:.8rem;font-weight:700;color:rgba(255,255,255,.65);letter-spacing:.5px;text-transform:uppercase}
        .lp-card-badge{display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:600;color:#34d399}
        .lp-progress-item{margin-bottom:.9rem}
        .lp-progress-item:last-child{margin-bottom:0}
        .lp-prog-row{display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:.4rem}
        .lp-prog-name{color:rgba(255,255,255,.6)}
        .lp-prog-val{color:rgba(255,255,255,.85);font-weight:600}
        .lp-prog-bar{height:5px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden}
        .lp-prog-fill{height:100%;border-radius:999px}
        .lp-stats{display:flex;gap:.75rem;margin-top:1.25rem}
        .lp-stat{flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:.75rem;text-align:center}
        .lp-stat-num{font-size:1.2rem;font-weight:900;background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .lp-stat-lbl{font-size:.65rem;color:rgba(255,255,255,.35);margin-top:2px}
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
        .form-head h2{font-size:1.75rem;font-weight:900;letter-spacing:-1px;margin-bottom:.4rem;color:#f0eeff}
        .form-head p{font-size:.875rem;color:rgba(255,255,255,.4)}

        .alert{display:flex;align-items:center;gap:.75rem;padding:.9rem 1rem;border-radius:12px;font-size:.82rem;margin-bottom:1.25rem}
        .alert-success{background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.25);color:#6ee7b7}
        .alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#fca5a5}

        .form-group{margin-bottom:1.1rem}
        .form-label{display:block;font-size:.8rem;font-weight:600;color:rgba(255,255,255,.6);margin-bottom:.5rem;letter-spacing:.2px}
        .input-wrap{position:relative}
        .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);pointer-events:none;display:flex}
        .form-input{
            width:100%;padding:.85rem 1rem .85rem 2.75rem;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.1);
            border-radius:12px;font-size:.875rem;color:#f0eeff;
            outline:none;transition:all .2s;font-family:inherit;
        }
        .form-input::placeholder{color:rgba(255,255,255,.25)}
        .form-input:focus{border-color:rgba(139,92,246,.6);background:rgba(139,92,246,.08);box-shadow:0 0 0 3px rgba(139,92,246,.15);}
        .input-action{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);
            background:none;border:none;color:rgba(255,255,255,.3);cursor:pointer;padding:.2rem;transition:color .2s;display:flex}
        .input-action:hover{color:rgba(255,255,255,.7)}

        .toggle-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem}
        .toggle-track{width:38px;height:21px;border-radius:999px;background:rgba(255,255,255,.15);position:relative;cursor:pointer;flex-shrink:0;transition:background .25s;border:1px solid rgba(255,255,255,.1)}
        .toggle-track.on{background:linear-gradient(135deg,#8b5cf6,#6d28d9);border-color:transparent}
        .toggle-thumb{position:absolute;top:2px;left:2px;width:15px;height:15px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.4);transition:transform .25s}
        .toggle-track.on .toggle-thumb{transform:translateX(17px)}
        .toggle-lbl{font-size:.82rem;color:rgba(255,255,255,.45);cursor:pointer;user-select:none}

        .btn-submit{
            width:100%;padding:.95rem;border:none;border-radius:12px;
            background:linear-gradient(135deg,#8b5cf6,#6d28d9);
            color:#fff;font-size:.925rem;font-weight:700;font-family:inherit;
            cursor:pointer;transition:all .25s;
            box-shadow:0 0 25px rgba(139,92,246,.4),0 4px 15px rgba(0,0,0,.3);
            position:relative;overflow:hidden;
        }
        .btn-submit::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent);
            animation:shimmer 2.4s infinite}
        @keyframes shimmer{0%{left:-100%}100%{left:160%}}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 0 40px rgba(139,92,246,.6),0 8px 25px rgba(0,0,0,.4)}
        .btn-inner{display:flex;align-items:center;justify-content:center;gap:.5rem}

        .divider{display:flex;align-items:center;gap:.9rem;margin:1.25rem 0}
        .divider-line{flex:1;height:1px;background:rgba(255,255,255,.08)}
        .divider-text{font-size:.75rem;color:rgba(255,255,255,.25)}

        .btn-secondary{
            display:flex;align-items:center;justify-content:center;gap:.5rem;
            width:100%;padding:.85rem;border-radius:12px;
            background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
            color:rgba(255,255,255,.6);font-size:.875rem;font-weight:600;font-family:inherit;
            text-decoration:none;cursor:pointer;transition:all .2s;
        }
        .btn-secondary:hover{background:rgba(139,92,246,.12);border-color:rgba(139,92,246,.35);color:#c4b5fd}
        .btn-secondary .accent{color:#a78bfa}

        .forgot-link{display:block;text-align:right;font-size:.78rem;color:rgba(255,255,255,.35);text-decoration:none;margin-top:.5rem;transition:color .2s}
        .forgot-link:hover{color:#a78bfa}

        .form-footer{text-align:center;font-size:.75rem;color:rgba(255,255,255,.2);margin-top:2.5rem}

        /* lang switcher */
        .lang-switcher{position:absolute;top:1.5rem;right:1.5rem;display:flex;gap:4px;z-index:10}
        .lang-btn{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:rgba(255,255,255,.4);cursor:pointer;font-family:inherit;transition:all .15s}
        .lang-btn.active{background:rgba(139,92,246,.25);border-color:rgba(139,92,246,.5);color:#c4b5fd}
        .lang-btn:hover{color:#fff;border-color:rgba(255,255,255,.35)}

        @media(max-width:900px){
            .left-panel{display:none}
            .right-panel{width:100%;border-left:none}
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
                    <div class="lp-prog-bar"><div class="lp-prog-fill" style="width:68%;background:linear-gradient(90deg,#8b5cf6,#06b6d4)"></div></div>
                </div>
                <div class="lp-progress-item">
                    <div class="lp-prog-row"><span class="lp-prog-name">{{ __('auth.login_project2') }}</span><span class="lp-prog-val">41%</span></div>
                    <div class="lp-prog-bar"><div class="lp-prog-fill" style="width:41%;background:linear-gradient(90deg,#ec4899,#8b5cf6)"></div></div>
                </div>
                <div class="lp-progress-item">
                    <div class="lp-prog-row"><span class="lp-prog-name">{{ __('auth.login_project3') }}</span><span class="lp-prog-val">100%</span></div>
                    <div class="lp-prog-bar"><div class="lp-prog-fill" style="width:100%;background:linear-gradient(90deg,#34d399,#06b6d4)"></div></div>
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
                    <div class="lp-notif-dot" style="background:#8b5cf6"></div>
                    <span class="lp-notif-text">{{ __('auth.login_notif1') }}</span>
                </div>
                <div class="lp-notif-item">
                    <div class="lp-notif-dot" style="background:#06b6d4"></div>
                    <span class="lp-notif-text">{{ __('auth.login_notif2') }}</span>
                </div>
                <div class="lp-notif-item">
                    <div class="lp-notif-dot" style="background:#34d399"></div>
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
        this.r=Math.random()*1.2+.4; this.alpha=Math.random()*.4+.1;
        this.color=['#8b5cf6','#06b6d4','#34d399','#a78bfa'][Math.floor(Math.random()*4)];
    }
    update(){ this.x+=this.vx; this.y+=this.vy; if(this.x<0||this.x>W||this.y<0||this.y>H) this.reset(); }
    draw(){ ctx.beginPath(); ctx.arc(this.x,this.y,this.r,0,Math.PI*2); ctx.fillStyle=this.color; ctx.globalAlpha=this.alpha; ctx.fill(); }
}
for(let i=0;i<80;i++) particles.push(new Particle());
function drawConnections(){
    for(let i=0;i<particles.length;i++) for(let j=i+1;j<particles.length;j++){
        const dx=particles[i].x-particles[j].x, dy=particles[i].y-particles[j].y;
        const dist=Math.sqrt(dx*dx+dy*dy);
        if(dist<80){ ctx.beginPath(); ctx.moveTo(particles[i].x,particles[i].y); ctx.lineTo(particles[j].x,particles[j].y); ctx.strokeStyle='#8b5cf6'; ctx.globalAlpha=(1-dist/80)*.1; ctx.lineWidth=.5; ctx.stroke(); }
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
    input.addEventListener('focus', () => { input.closest('.input-wrap').querySelector('.input-icon').style.color = 'rgba(167,139,250,.7)'; });
    input.addEventListener('blur',  () => { input.closest('.input-wrap').querySelector('.input-icon').style.color = ''; });
});
</script>
</body>
</html>
