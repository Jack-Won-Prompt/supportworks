<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.forgot_form_title') }} — SupportWorks</title>
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
        .lp-desc{font-size:.87rem;color:rgba(255,255,255,.45);line-height:1.7;margin-bottom:2.5rem}

        .lp-steps{display:flex;flex-direction:column;gap:1rem}
        .lp-step{display:flex;align-items:flex-start;gap:1rem}
        .lp-step-num{width:28px;height:28px;border-radius:50%;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;
            font-size:.7rem;font-weight:800;
            background:rgba(139,92,246,.2);border:1px solid rgba(139,92,246,.4);color:#c4b5fd}
        .lp-step-body{}
        .lp-step-title{font-size:.8rem;font-weight:700;color:rgba(255,255,255,.75);margin-bottom:.2rem}
        .lp-step-desc{font-size:.73rem;color:rgba(255,255,255,.35);line-height:1.5}

        .lp-security{position:relative;z-index:1;display:flex;align-items:center;gap:.6rem;
            background:rgba(52,211,153,.06);border:1px solid rgba(52,211,153,.15);
            border-radius:12px;padding:.75rem 1rem;margin-top:.5rem}
        .lp-security-icon{color:#34d399;flex-shrink:0;display:flex}
        .lp-security-text{font-size:.75rem;color:rgba(255,255,255,.4);line-height:1.5}
        .lp-security-text strong{color:#6ee7b7;font-weight:600}

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
        .alert-success{background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.25);color:#6ee7b7}
        .alert-success .alert-icon{color:#34d399;flex-shrink:0;margin-top:1px}
        .alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
        .alert-error .alert-icon{color:#f87171;flex-shrink:0;margin-top:1px}

        .form-group{margin-bottom:1.25rem}
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
        .form-input:focus{
            border-color:rgba(139,92,246,.6);
            background:rgba(139,92,246,.08);
            box-shadow:0 0 0 3px rgba(139,92,246,.15);
        }

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
        .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}

        .back-link{
            display:flex;align-items:center;justify-content:center;gap:.45rem;
            width:100%;padding:.8rem;border-radius:12px;margin-top:.9rem;
            background:transparent;border:1px solid rgba(255,255,255,.08);
            color:rgba(255,255,255,.4);font-size:.85rem;font-weight:500;font-family:inherit;
            text-decoration:none;cursor:pointer;transition:all .2s;
        }
        .back-link:hover{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.15);color:rgba(255,255,255,.7)}

        .form-footer{text-align:center;font-size:.75rem;color:rgba(255,255,255,.2);margin-top:2.5rem}

        /* success state */
        .success-state{text-align:center;padding:1rem 0}
        .success-icon-wrap{width:72px;height:72px;border-radius:50%;margin:0 auto 1.5rem;
            background:rgba(52,211,153,.1);border:2px solid rgba(52,211,153,.3);
            display:flex;align-items:center;justify-content:center;color:#34d399;
            animation:scaleIn .4s ease}
        @keyframes scaleIn{0%{transform:scale(0.5);opacity:0}100%{transform:scale(1);opacity:1}}
        .success-title{font-size:1.4rem;font-weight:800;color:#f0eeff;letter-spacing:-.5px;margin-bottom:.6rem}
        .success-desc{font-size:.875rem;color:rgba(255,255,255,.45);line-height:1.7;margin-bottom:.4rem}
        .success-email{font-size:.875rem;font-weight:600;color:#a78bfa;word-break:break-all}
        .success-note{font-size:.78rem;color:rgba(255,255,255,.25);margin-top:.75rem;line-height:1.6}
        .success-divider{height:1px;background:rgba(255,255,255,.07);margin:1.5rem 0}

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
            {{ __('auth.forgot_badge') }}
        </div>
        <h2 class="lp-title">
            {!! __('auth.forgot_headline') !!}
        </h2>
        <p class="lp-desc">{{ __('auth.forgot_desc') }}</p>

        <div class="lp-steps">
            <div class="lp-step">
                <div class="lp-step-num">1</div>
                <div class="lp-step-body">
                    <div class="lp-step-title">{{ __('auth.forgot_step1_title') }}</div>
                    <div class="lp-step-desc">{{ __('auth.forgot_step1_desc') }}</div>
                </div>
            </div>
            <div class="lp-step">
                <div class="lp-step-num">2</div>
                <div class="lp-step-body">
                    <div class="lp-step-title">{{ __('auth.forgot_step2_title') }}</div>
                    <div class="lp-step-desc">{{ __('auth.forgot_step2_desc') }}</div>
                </div>
            </div>
            <div class="lp-step">
                <div class="lp-step-num">3</div>
                <div class="lp-step-body">
                    <div class="lp-step-title">{{ __('auth.forgot_step3_title') }}</div>
                    <div class="lp-step-desc">{{ __('auth.forgot_step3_desc') }}</div>
                </div>
            </div>
        </div>

        <div class="lp-security" style="margin-top:1.75rem">
            <div class="lp-security-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="lp-security-text">
                {!! __('auth.forgot_security') !!}
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

        @if(session('status'))
        <!-- ── SUCCESS STATE ── -->
        <div class="success-state">
            <div class="success-icon-wrap">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="success-title">{{ __('auth.forgot_success_title') }}</div>
            <div class="success-desc">{{ __('auth.forgot_success_desc') }}</div>
            <div class="success-note">{{ __('auth.forgot_success_note') }}<br>{!! __('auth.forgot_link_expiry') !!}</div>
            <div class="success-divider"></div>
            <a href="{{ route('login') }}" class="back-link">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ __('auth.forgot_back') }}
            </a>
            <form method="POST" action="{{ route('password.email') }}" style="margin-top:.6rem">
                @csrf
                <input type="hidden" name="email" value="{{ old('email') }}">
                <button type="submit" class="back-link" style="border-color:rgba(139,92,246,.25);color:rgba(167,139,250,.6)">
                    {{ __('auth.forgot_resend') }}
                </button>
            </form>
        </div>

        @else
        <!-- ── FORM STATE ── -->
        <div class="form-head">
            <div class="form-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h2>{{ __('auth.forgot_form_title') }}</h2>
            <p>{!! __('auth.forgot_form_sub') !!}</p>
        </div>

        @if($errors->any())
        <div class="alert alert-error">
            <div class="alert-icon">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            </div>
            <div>{{ $errors->first('email') }}</div>
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="reset-form">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">{{ __('auth.forgot_email') }}</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="form-input" placeholder="your@email.com">
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submit-btn">
                <span class="btn-inner" id="btn-text">
                    {{ __('auth.forgot_submit') }}
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </span>
            </button>
        </form>

        <a href="{{ route('login') }}" class="back-link">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('auth.forgot_back') }}
        </a>
        @endif

        <p class="form-footer">{{ str_replace(':year', date('Y'), __('auth.footer')) }}</p>
    </div>
</div>

<script>
const FORGOT_STR = { sending: '{{ __("auth.forgot_sending") }}' };
// ── Particle canvas ──
const canvas = document.getElementById('left-canvas');
if (canvas) {
    const ctx = canvas.getContext('2d');
    let W, H, particles = [];

    function resize() {
        W = canvas.width = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    class Particle {
        constructor() { this.reset(); }
        reset() {
            this.x = Math.random() * W;
            this.y = Math.random() * H;
            this.vx = (Math.random() - 0.5) * 0.3;
            this.vy = (Math.random() - 0.5) * 0.3;
            this.r = Math.random() * 1.2 + 0.4;
            this.alpha = Math.random() * 0.4 + 0.1;
            const colors = ['#8b5cf6','#06b6d4','#34d399','#a78bfa'];
            this.color = colors[Math.floor(Math.random() * colors.length)];
        }
        update() {
            this.x += this.vx; this.y += this.vy;
            if (this.x < 0 || this.x > W || this.y < 0 || this.y > H) this.reset();
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.globalAlpha = this.alpha;
            ctx.fill();
        }
    }

    for (let i = 0; i < 80; i++) particles.push(new Particle());

    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 80) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = '#8b5cf6';
                    ctx.globalAlpha = (1 - dist / 80) * 0.1;
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }
            }
        }
    }

    function animate() {
        ctx.clearRect(0, 0, W, H);
        ctx.globalAlpha = 1;
        drawConnections();
        particles.forEach(p => { p.update(); p.draw(); });
        ctx.globalAlpha = 1;
        requestAnimationFrame(animate);
    }
    animate();
}

// ── Submit loading state ──
const form = document.getElementById('reset-form');
const submitBtn = document.getElementById('submit-btn');
const btnText = document.getElementById('btn-text');
if (form) {
    form.addEventListener('submit', () => {
        if (submitBtn) {
            submitBtn.disabled = true;
            btnText.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin .8s linear infinite">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                ${FORGOT_STR.sending}
            `;
        }
    });
}

// ── Input focus glow ──
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', () => {
        input.closest('.input-wrap').querySelector('.input-icon').style.color = 'rgba(167,139,250,.7)';
    });
    input.addEventListener('blur', () => {
        input.closest('.input-wrap').querySelector('.input-icon').style.color = '';
    });
});
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
