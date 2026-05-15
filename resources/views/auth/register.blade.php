<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.register_title') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{font-family:'Inter','Noto Sans KR',sans-serif;background:#ffffff;color:#0f172a;display:flex;height:100vh;overflow:hidden}

        /* ── LEFT PANEL ── */
        .left-panel{
            position:relative;width:44%;height:100vh;
            display:flex;flex-direction:column;
            justify-content:space-between;
            padding:2.6rem 3rem;overflow:hidden;flex-shrink:0;
            background:linear-gradient(150deg,#f8fafc 0%,#eef2ff 60%,#e0e7ff 100%);
            border-right:1px solid #e2e8f0;
        }
        #left-canvas{position:absolute;inset:0;z-index:0}
        .lp-glow1{position:absolute;top:-80px;right:-80px;width:380px;height:380px;border-radius:50%;
            background:radial-gradient(circle,rgba(99,102,241,.18) 0%,transparent 70%);pointer-events:none;z-index:0}
        .lp-glow2{position:absolute;bottom:-80px;left:-80px;width:280px;height:280px;border-radius:50%;
            background:radial-gradient(circle,rgba(6,182,212,.13) 0%,transparent 70%);pointer-events:none;z-index:0}

        .lp-logo{position:relative;z-index:1;display:flex;align-items:center;gap:1rem;flex-shrink:0}
        .lp-logo-icon{width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#4f46e5);
            display:flex;align-items:center;justify-content:center;font-size:1.55rem;color:#fff;box-shadow:0 12px 28px -8px rgba(79,70,229,.4)}
        .lp-logo-text{font-size:1.5rem;font-weight:900;color:#0f172a;letter-spacing:-.5px}

        /* 중앙 영역 */
        .lp-center{position:relative;z-index:1;flex:1;min-height:0;display:flex;flex-direction:column;justify-content:center;gap:1.3rem;padding:.65rem 0}
        .lp-title{font-size:clamp(1.95rem,3.1vw,2.75rem);font-weight:900;letter-spacing:-2px;line-height:1.2;color:#0f172a}
        .lp-title .grad{background:linear-gradient(90deg,#4f46e5,#0891b2);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .lp-desc{font-size:1.15rem;color:#475569;line-height:1.7;max-width:340px}

        /* ── 웍스 이미지 3열 갤러리 ── */
        .ai-gallery{display:flex;align-items:stretch;gap:.9rem;width:100%;height:228px}

        /* 사이드 카드 */
        .ai-side-card{
            flex:1;border-radius:11px;overflow:hidden;
            border:1px solid #e2e8f0;
            background:#ffffff;
            box-shadow:0 8px 20px -8px rgba(15,23,42,.08);
            display:flex;align-items:center;justify-content:center;
        }
        .ai-side-card:first-child{animation:floatA 4.5s ease-in-out infinite}
        .ai-side-card:last-child {animation:floatB 5s   ease-in-out infinite}
        @keyframes floatA{0%,100%{transform:translateY(0)}  50%{transform:translateY(-6px)}}
        @keyframes floatB{0%,100%{transform:translateY(-4px)}50%{transform:translateY(3px)}}
        .ai-side-card svg{display:block;width:100%;height:100%}

        /* 중앙 오브 */
        .ai-orb-wrap{flex-shrink:0;display:flex;align-items:center;justify-content:center;width:163px}
        .ai-orb{
            width:150px;height:150px;border-radius:50%;
            background:radial-gradient(circle at 35% 35%,rgba(129,140,248,.22) 0%,rgba(99,102,241,.08) 40%,transparent 70%);
            border:1px solid rgba(99,102,241,.2);
            position:relative;display:flex;align-items:center;justify-content:center;
            animation:floatOrb 4s ease-in-out infinite;
        }
        @keyframes floatOrb{0%,100%{transform:translateY(-3px)}50%{transform:translateY(4px)}}
        .ai-orb::before{
            content:'';position:absolute;inset:-2px;border-radius:50%;
            background:conic-gradient(from 0deg,transparent 0%,rgba(99,102,241,.55) 20%,transparent 40%,rgba(6,182,212,.4) 60%,transparent 80%,rgba(16,185,129,.4) 100%);
            animation:spin 8s linear infinite;z-index:-1;
            -webkit-mask:radial-gradient(circle,transparent 88%,black 100%);
            mask:radial-gradient(circle,transparent 88%,black 100%);
        }
        @keyframes spin{to{transform:rotate(360deg)}}
        .orb-icon{font-size:3.1rem;filter:drop-shadow(0 6px 18px rgba(79,70,229,.45))}

        /* step list */
        .steps-list{display:flex;flex-direction:column;gap:.7rem}
        .step-item{display:flex;align-items:center;gap:1.1rem;padding:.9rem 1.3rem;border-radius:14px;
            background:#ffffff;border:1px solid #e2e8f0;transition:all .2s;
            box-shadow:0 1px 2px rgba(15,23,42,.03)}
        .step-item.active{background:#eef2ff;border-color:#c7d2fe}
        .step-num{width:36px;height:36px;border-radius:50%;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;font-size:.975rem;font-weight:800;
            background:#f1f5f9;color:#94a3b8}
        .step-item.active .step-num{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 6px 16px -4px rgba(79,70,229,.45)}
        .step-text{flex:1}
        .step-name{font-size:1.1rem;font-weight:700;color:#334155}
        .step-item.active .step-name{color:#4338ca}
        .step-sub{font-size:.95rem;color:#94a3b8;margin-top:2px}
        .step-check{font-size:1rem;color:#10b981}

        .lp-features{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:.85rem;flex-shrink:0}
        .lp-feat{display:inline-flex;align-items:center;gap:.5rem;font-size:.975rem;color:#475569}
        .lp-feat::before{content:'✓';color:#10b981;font-weight:700}

        /* ── RIGHT PANEL ── */
        .right-panel{
            flex:1;height:100vh;overflow-y:auto;
            display:flex;align-items:flex-start;justify-content:center;
            padding:2rem 2rem;
            background:#ffffff;
        }
        .form-box{width:100%;max-width:440px;padding:.5rem 0}

        .mobile-logo{display:none;align-items:center;gap:.65rem;justify-content:center;margin-bottom:2.5rem}
        .mobile-logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff}
        .mobile-logo-text{font-size:1rem;font-weight:900;color:#0f172a}

        .form-head{margin-bottom:1.75rem}
        .form-head h2{font-size:1.75rem;font-weight:900;letter-spacing:-1px;margin-bottom:.4rem;color:#0f172a}
        .form-head p{font-size:.875rem;color:#64748b}

        .alert{display:flex;align-items:center;gap:.75rem;padding:.9rem 1rem;border-radius:12px;font-size:.82rem;margin-bottom:1.25rem}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}

        .form-row{display:grid;gap:1rem}
        .form-row.cols2{grid-template-columns:1fr 1fr}
        .form-group{margin-bottom:1rem}
        .form-label{display:flex;align-items:center;gap:.3rem;font-size:.78rem;font-weight:600;color:#334155;margin-bottom:.45rem;letter-spacing:.2px}
        .form-label .req{color:#4f46e5;font-weight:800}
        .form-label .opt{font-weight:400;color:#94a3b8;font-size:.7rem}

        .input-wrap{position:relative}
        .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;display:flex}
        .form-input{
            width:100%;padding:.82rem 1rem .82rem 2.7rem;
            background:#ffffff;border:1px solid #e2e8f0;
            border-radius:11px;font-size:.875rem;color:#0f172a;outline:none;
            transition:all .2s;font-family:inherit;
        }
        .form-input::placeholder{color:#cbd5e1}
        .form-input:focus{
            border-color:#6366f1;
            box-shadow:0 0 0 4px rgba(99,102,241,.12);
        }
        .form-input.error-input{border-color:#fca5a5;background:#fef2f2}
        .field-error{font-size:.73rem;color:#dc2626;margin-top:.35rem}

        /* password strength */
        .pw-strength{margin-top:.5rem}
        .pw-strength-bar{height:4px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin-bottom:.3rem}
        .pw-strength-fill{height:100%;width:0%;border-radius:999px;transition:width .3s,background .3s}
        .pw-strength-text{font-size:.7rem;color:#94a3b8}

        .btn-submit{
            width:100%;padding:.95rem;border:none;border-radius:12px;
            background:linear-gradient(135deg,#6366f1,#4f46e5);
            color:#fff;font-size:.925rem;font-weight:700;font-family:inherit;
            cursor:pointer;transition:all .25s;
            box-shadow:0 10px 24px -8px rgba(79,70,229,.45),0 2px 4px rgba(15,23,42,.06);
            position:relative;overflow:hidden;margin-top:.5rem;
        }
        .btn-submit::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.22),transparent);
            animation:shimmer 2.4s infinite}
        @keyframes shimmer{0%{left:-100%}100%{left:160%}}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 14px 32px -8px rgba(79,70,229,.55),0 4px 8px rgba(15,23,42,.08)}
        .btn-inner{display:flex;align-items:center;justify-content:center;gap:.5rem}

        .divider{display:flex;align-items:center;gap:.9rem;margin:1.25rem 0}
        .divider-line{flex:1;height:1px;background:#e2e8f0}
        .divider-text{font-size:.72rem;color:#94a3b8;white-space:nowrap}

        .btn-secondary{
            display:flex;align-items:center;justify-content:center;gap:.5rem;
            width:100%;padding:.85rem;border-radius:12px;
            background:#ffffff;border:1px solid #e2e8f0;
            color:#475569;font-size:.875rem;font-weight:600;font-family:inherit;
            text-decoration:none;cursor:pointer;transition:all .2s;
        }
        .btn-secondary:hover{background:#eef2ff;border-color:#c7d2fe;color:#4338ca}
        .btn-secondary .accent{color:#4f46e5}

        .terms-note{font-size:.72rem;color:#94a3b8;text-align:center;line-height:1.6;margin-top:1rem}
        .terms-note a{color:#6366f1;text-decoration:none}
        .terms-note a:hover{color:#4338ca;text-decoration:underline}
        .form-footer{text-align:center;font-size:.72rem;color:#94a3b8;margin-top:1.75rem}

        /* 언어 스위처 */
        .lang-switch{display:flex;justify-content:flex-end;gap:4px;margin-bottom:1.5rem}
        .lang-btn{padding:3px 9px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid #e2e8f0;cursor:pointer;font-family:inherit;transition:all .15s;background:#ffffff;color:#64748b}
        .lang-btn.active{background:#eef2ff;color:#4338ca;border-color:#c7d2fe}
        .lang-btn:not(.active):hover{background:#f8fafc;color:#0f172a}

        /* ── 자동완성 콤보박스 ── */
        .combo-wrap{position:relative}
        .combo-input-row{position:relative}
        .combo-input-row .input-icon{z-index:2}
        .combo-input{
            width:100%;padding:.82rem 2.6rem .82rem 2.7rem;
            background:#ffffff;border:1px solid #e2e8f0;
            border-radius:11px;font-size:.875rem;color:#0f172a;outline:none;
            transition:all .2s;font-family:inherit;
        }
        .combo-input::placeholder{color:#cbd5e1}
        .combo-input:focus{
            border-color:#6366f1;
            box-shadow:0 0 0 4px rgba(99,102,241,.12);
        }
        .combo-clear{
            position:absolute;right:.8rem;top:50%;transform:translateY(-50%);
            background:none;border:none;color:#94a3b8;cursor:pointer;
            padding:.2rem;display:none;align-items:center;transition:color .2s;font-size:.9rem;line-height:1;
        }
        .combo-clear:hover{color:#475569}
        .combo-clear.visible{display:flex}
        .combo-dropdown{
            position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:100;
            background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;
            box-shadow:0 18px 40px -12px rgba(15,23,42,.18),0 4px 8px rgba(15,23,42,.04);
            overflow:hidden;display:none;
        }
        .combo-dropdown.open{display:block}
        .combo-list{max-height:220px;overflow-y:auto;padding:.35rem}
        .combo-list::-webkit-scrollbar{width:4px}
        .combo-list::-webkit-scrollbar-track{background:transparent}
        .combo-list::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
        .combo-item{
            display:flex;align-items:center;gap:.65rem;
            padding:.6rem .85rem;border-radius:8px;font-size:.84rem;color:#334155;
            cursor:pointer;transition:all .15s;
        }
        .combo-item:hover,.combo-item.focused{background:#eef2ff;color:#4338ca}
        .combo-item-icon{font-size:.8rem;opacity:.7}
        .combo-item-text{flex:1}
        .combo-item-text mark{background:none;color:#4f46e5;font-weight:700}
        .combo-item-badge{
            font-size:.65rem;font-weight:600;padding:.1rem .45rem;border-radius:999px;
            background:#eef2ff;color:#4f46e5;border:1px solid #e0e7ff;
        }
        .combo-new{
            display:flex;align-items:center;gap:.65rem;
            padding:.65rem .85rem;border-top:1px solid #f1f5f9;
            font-size:.82rem;color:#64748b;cursor:pointer;transition:all .15s;
        }
        .combo-new:hover,.combo-new.focused{background:#ecfdf5;color:#047857}
        .combo-new-icon{font-size:.85rem}
        .combo-empty{padding:.85rem;text-align:center;font-size:.8rem;color:#94a3b8}
        .combo-loading{padding:.75rem;text-align:center;font-size:.78rem;color:#64748b;display:flex;align-items:center;justify-content:center;gap:.5rem}
        .combo-spinner{width:12px;height:12px;border:2px solid #e0e7ff;border-top-color:#4f46e5;border-radius:50%;animation:cspin .7s linear infinite}
        @keyframes cspin{to{transform:rotate(360deg)}}

        @media(max-width:1000px){
            body{height:auto;overflow:auto}
            .left-panel{display:none}
            .right-panel{width:100%;height:auto;min-height:100vh;align-items:center}
            .mobile-logo{display:flex}
        }
        @media(max-width:480px){
            .right-panel{padding:1.5rem 1.25rem}
            .form-row.cols2{grid-template-columns:1fr}
        }
    </style>
</head>
<body>

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
        <h2 class="lp-title">
            {{ __('auth.register_headline1') }}<br><span class="grad">{{ __('auth.register_headline2') }}</span>
        </h2>
        <p class="lp-desc">{{ __('auth.register_desc') }}</p>

        <div class="ai-gallery">

            {{-- 왼쪽 카드: 웍스 채팅 --}}
            <div class="ai-side-card">
                <svg viewBox="0 0 120 180" preserveAspectRatio="xMidYMid meet" style="height:100%;width:100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="sg1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#ffffff"/>
                            <stop offset="100%" stop-color="#eef2ff"/>
                        </linearGradient>
                        <radialGradient id="sg1g" cx="80%" cy="10%" r="60%">
                            <stop offset="0%" stop-color="rgba(6,182,212,.18)"/>
                            <stop offset="100%" stop-color="transparent"/>
                        </radialGradient>
                    </defs>
                    <rect width="120" height="180" fill="url(#sg1)"/>
                    <rect width="120" height="180" fill="url(#sg1g)"/>
                    <!-- header -->
                    <rect x="0" y="0" width="120" height="28" fill="rgba(15,23,42,.03)"/>
                    <circle cx="12" cy="14" r="5" fill="rgba(99,102,241,.6)"/>
                    <rect x="20" y="11" width="40" height="5" rx="2.5" fill="rgba(15,23,42,.18)"/>
                    <circle cx="108" cy="14" r="4" fill="rgba(16,185,129,.55)"/>
                    <!-- chat bubbles -->
                    <rect x="8" y="35" width="72" height="22" rx="8" fill="rgba(99,102,241,.1)" stroke="rgba(99,102,241,.28)" stroke-width=".6"/>
                    <rect x="12" y="41" width="50" height="4" rx="2" fill="rgba(15,23,42,.5)"/>
                    <rect x="12" y="48" width="36" height="4" rx="2" fill="rgba(15,23,42,.3)"/>

                    <rect x="28" y="65" width="84" height="22" rx="8" fill="rgba(6,182,212,.12)" stroke="rgba(6,182,212,.28)" stroke-width=".6"/>
                    <rect x="32" y="71" width="55" height="4" rx="2" fill="rgba(15,23,42,.45)"/>
                    <rect x="32" y="78" width="40" height="4" rx="2" fill="rgba(15,23,42,.28)"/>

                    <rect x="8" y="95" width="88" height="36" rx="8" fill="rgba(99,102,241,.08)" stroke="rgba(99,102,241,.25)" stroke-width=".6"/>
                    <rect x="13" y="101" width="30" height="4" rx="2" fill="rgba(79,70,229,.65)"/>
                    <circle cx="13" cy="113" r="2.5" fill="#10b981"/>
                    <rect x="19" y="111" width="45" height="3.5" rx="1.5" fill="rgba(15,23,42,.35)"/>
                    <circle cx="13" cy="121" r="2.5" fill="#f59e0b"/>
                    <rect x="19" y="119" width="38" height="3.5" rx="1.5" fill="rgba(15,23,42,.3)"/>

                    <!-- typing indicator -->
                    <rect x="28" y="140" width="42" height="18" rx="9" fill="rgba(99,102,241,.1)" stroke="rgba(99,102,241,.22)" stroke-width=".6"/>
                    <circle cx="38" cy="149" r="2.5" fill="#6366f1">
                        <animate attributeName="opacity" values="1;.3;1" dur="1.2s" repeatCount="indefinite" begin="0s"/>
                    </circle>
                    <circle cx="47" cy="149" r="2.5" fill="#6366f1">
                        <animate attributeName="opacity" values="1;.3;1" dur="1.2s" repeatCount="indefinite" begin=".3s"/>
                    </circle>
                    <circle cx="56" cy="149" r="2.5" fill="#6366f1">
                        <animate attributeName="opacity" values="1;.3;1" dur="1.2s" repeatCount="indefinite" begin=".6s"/>
                    </circle>
                    <!-- input bar -->
                    <rect x="8" y="164" width="104" height="12" rx="6" fill="#ffffff" stroke="#e2e8f0" stroke-width=".6"/>
                    <rect x="94" y="166" width="14" height="8" rx="4" fill="rgba(79,70,229,.6)"/>
                    <rect x="14" y="167" width="35" height="3" rx="1.5" fill="rgba(15,23,42,.22)"/>
                    <!-- label -->
                    <text x="60" y="177" fill="#94a3b8" font-size="5.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_ai_chat') }}</text>
                </svg>
            </div>

            {{-- 중앙: 웍스 오브 --}}
            <div class="ai-orb-wrap">
                <div class="ai-orb">
                    <span class="orb-icon">🤖</span>
                </div>
            </div>

            {{-- 오른쪽 카드: 분석 대시보드 --}}
            <div class="ai-side-card">
                <svg viewBox="0 0 120 180" preserveAspectRatio="xMidYMid meet" style="height:100%;width:100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="sg2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#ffffff"/>
                            <stop offset="100%" stop-color="#ecfdf5"/>
                        </linearGradient>
                        <radialGradient id="sg2g" cx="20%" cy="80%" r="60%">
                            <stop offset="0%" stop-color="rgba(16,185,129,.15)"/>
                            <stop offset="100%" stop-color="transparent"/>
                        </radialGradient>
                    </defs>
                    <rect width="120" height="180" fill="url(#sg2)"/>
                    <rect width="120" height="180" fill="url(#sg2g)"/>
                    <!-- header -->
                    <rect x="0" y="0" width="120" height="28" fill="rgba(15,23,42,.03)"/>
                    <rect x="8" y="10" width="45" height="6" rx="3" fill="rgba(15,23,42,.25)"/>
                    <rect x="95" y="11" width="18" height="6" rx="3" fill="rgba(16,185,129,.45)"/>
                    <!-- stat chips -->
                    <rect x="8" y="35" width="48" height="30" rx="8" fill="rgba(99,102,241,.08)" stroke="rgba(99,102,241,.28)" stroke-width=".7"/>
                    <text x="32" y="52" fill="#4f46e5" font-size="12" font-weight="800" text-anchor="middle" font-family="Inter,sans-serif">73%</text>
                    <text x="32" y="60" fill="#64748b" font-size="5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_productivity') }}</text>

                    <rect x="64" y="35" width="48" height="30" rx="8" fill="rgba(16,185,129,.08)" stroke="rgba(16,185,129,.25)" stroke-width=".7"/>
                    <text x="88" y="52" fill="#059669" font-size="12" font-weight="800" text-anchor="middle" font-family="Inter,sans-serif">48</text>
                    <text x="88" y="60" fill="#64748b" font-size="5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_done_tasks') }}</text>

                    <!-- bar chart -->
                    <rect x="8" y="74" width="104" height="58" rx="8" fill="#ffffff" stroke="#e2e8f0" stroke-width=".7"/>
                    <text x="16" y="84" fill="#334155" font-size="5.5" font-family="Inter,sans-serif">{{ __('auth.register_svg_weekly') }}</text>
                    <!-- bars -->
                    <rect x="16"  y="110" width="9" height="16" rx="2" fill="rgba(99,102,241,.5)"/>
                    <rect x="29"  y="103" width="9" height="23" rx="2" fill="rgba(99,102,241,.7)"/>
                    <rect x="42"  y="108" width="9" height="18" rx="2" fill="rgba(99,102,241,.5)"/>
                    <rect x="55"  y="98"  width="9" height="28" rx="2" fill="rgba(6,182,212,.75)"/>
                    <rect x="68"  y="105" width="9" height="21" rx="2" fill="rgba(99,102,241,.55)"/>
                    <rect x="81"  y="93"  width="9" height="33" rx="2" fill="rgba(16,185,129,.85)"/>
                    <rect x="94"  y="100" width="9" height="26" rx="2" fill="rgba(6,182,212,.65)"/>
                    <!-- x labels -->
                    <text x="20"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_mon') }}</text>
                    <text x="33"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_tue') }}</text>
                    <text x="46"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_wed') }}</text>
                    <text x="59"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_thu') }}</text>
                    <text x="72"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_fri') }}</text>
                    <text x="85"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_sat') }}</text>
                    <text x="98"  y="132" fill="#94a3b8" font-size="4.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_sun') }}</text>

                    <!-- progress bars -->
                    <rect x="8" y="142" width="104" height="28" rx="8" fill="#ffffff" stroke="#e2e8f0" stroke-width=".7"/>
                    <text x="14" y="152" fill="#475569" font-size="5" font-family="Inter,sans-serif">{{ __('auth.register_svg_api') }}</text>
                    <rect x="14" y="156" width="75" height="4" rx="2" fill="#f1f5f9"/>
                    <rect x="14" y="156" width="64" height="4" rx="2" fill="url(#pg1)">
                        <defs>
                            <linearGradient id="pg1" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#6366f1"/>
                                <stop offset="100%" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                    </rect>
                    <text x="92" y="160" fill="#0891b2" font-size="5" font-weight="700" font-family="Inter,sans-serif">85%</text>

                    <!-- label -->
                    <text x="60" y="177" fill="#94a3b8" font-size="5.5" text-anchor="middle" font-family="Inter,sans-serif">{{ __('auth.register_svg_ai_analysis') }}</text>
                </svg>
            </div>

        </div>

        <div class="steps-list">
            <div class="step-item active">
                <div class="step-num">1</div>
                <div class="step-text">
                    <div class="step-name">{{ __('auth.register_step1_name') }}</div>
                    <div class="step-sub">{{ __('auth.register_step1_sub') }}</div>
                </div>
                <span class="step-check">●</span>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <div class="step-text">
                    <div class="step-name">{{ __('auth.register_step2_name') }}</div>
                    <div class="step-sub">{{ __('auth.register_step2_sub') }}</div>
                </div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div class="step-text">
                    <div class="step-name">{{ __('auth.register_step3_name') }}</div>
                    <div class="step-sub">{{ __('auth.register_step3_sub') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="lp-features">
        <div class="lp-feat">{{ __('auth.register_feat_free') }}</div>
        <div class="lp-feat">{{ __('auth.register_feat_instant') }}</div>
        <div class="lp-feat">{{ __('auth.register_feat_team') }}</div>
        <div class="lp-feat">{{ __('auth.register_feat_secure') }}</div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
    <div class="form-box">

        {{-- 언어 스위처 --}}
        <div class="lang-switch">
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

        <div class="mobile-logo">
            <div class="mobile-logo-icon">⚡</div>
            <span class="mobile-logo-text">SupportWorks</span>
        </div>

        <div class="form-head">
            <h2>{{ __('auth.register_form_title') }}</h2>
            <p>{{ __('auth.register_form_sub') }}</p>
        </div>

        @if($errors->any())
        <div class="alert alert-error">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            {{ __('auth.register_error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="register-form">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">{{ __('auth.register_label_name') }} <span class="req">*</span></label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="form-input @error('name') error-input @enderror" placeholder="{{ __('auth.register_placeholder_name') }}">
                </div>
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">{{ __('auth.register_label_email') }} <span class="req">*</span></label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           class="form-input @error('email') error-input @enderror" placeholder="your@email.com">
                </div>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="company-input">{{ __('auth.register_label_company') }} <span class="req">*</span></label>
                {{-- 실제 제출값 --}}
                <input type="hidden" name="company" id="company-value" value="{{ old('company') }}" required>
                <div class="combo-wrap">
                    <div class="combo-input-row">
                        <div class="input-icon" style="z-index:2">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <input id="company-input" type="text" autocomplete="off"
                               class="combo-input" placeholder="{{ __('auth.register_placeholder_company') }}"
                               value="{{ old('company') }}">
                        <button type="button" class="combo-clear" id="combo-clear" aria-label="{{ __('auth.clear') }}">✕</button>
                    </div>
                    <div class="combo-dropdown" id="combo-dropdown" role="listbox">
                        <div class="combo-list" id="combo-list"></div>
                    </div>
                </div>
                @error('company')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">{{ __('auth.register_label_password') }} <span class="req">*</span></label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <input id="password" type="password" name="password" required
                           class="form-input @error('password') error-input @enderror"
                           placeholder="{{ __('auth.register_placeholder_pw') }}" oninput="checkStrength(this.value)">
                </div>
                <div class="pw-strength">
                    <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-bar"></div></div>
                    <div class="pw-strength-text" id="pw-text">{{ __('auth.register_pw_hint') }}</div>
                </div>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">{{ __('auth.register_label_pw_confirm') }} <span class="req">*</span></label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="form-input" placeholder="{{ __('auth.register_placeholder_pw2') }}">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span class="btn-inner">
                    {{ __('auth.register_submit') }}
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </span>
            </button>

            <p class="terms-note">{{ __('auth.register_terms_pre') }} <a href="{{ route('policy.terms') }}" target="_blank">{{ __('auth.register_terms_tos') }}</a>{{ __('auth.register_terms_mid') }} <a href="{{ route('policy.privacy') }}" target="_blank">{{ __('auth.register_terms_pp') }}</a>{{ __('auth.register_terms_post') }}</p>
        </form>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">{{ __('auth.register_have_account') }}</span>
            <div class="divider-line"></div>
        </div>

        <a href="{{ route('login') }}" class="btn-secondary">
            <span class="accent">{{ __('auth.register_login_link') }}</span>
        </a>

        <p class="form-footer">© {{ date('Y') }} SupportWorks. All rights reserved.</p>
    </div>
</div>

<script>
// ── i18n strings for JS ──
const REG_STR = {
    strength_0:          '{{ __('auth.register_strength_0') }}',
    strength_1:          '{{ __('auth.register_strength_1') }}',
    strength_2:          '{{ __('auth.register_strength_2') }}',
    strength_3:          '{{ __('auth.register_strength_3') }}',
    strength_4:          '{{ __('auth.register_strength_4') }}',
    strength_5:          '{{ __('auth.register_strength_5') }}',
    combo_existing:      '{{ __('auth.register_combo_existing') }}',
    combo_direct:        '{{ __('auth.register_combo_direct') }}',
    combo_no_result:     '{{ __('auth.register_combo_no_result') }}',
    combo_searching:     '{{ __('auth.register_combo_searching') }}',
    combo_error:         '{{ __('auth.register_combo_error') }}',
    company_required:    '{{ __('auth.register_company_required') }}',
    company_placeholder: '{{ __('auth.register_company_placeholder_reset') }}',
};

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
            this.alpha = Math.random() * 0.32 + 0.08;
            const colors = ['#6366f1','#06b6d4','#10b981','#818cf8'];
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

    for (let i = 0; i < 70; i++) particles.push(new Particle());

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
                    ctx.strokeStyle = '#6366f1';
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

// ── Password strength ──
function checkStrength(val) {
    const bar = document.getElementById('pw-bar');
    const text = document.getElementById('pw-text');
    if (!bar) return;
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '0%',   color: 'transparent', label: REG_STR.strength_0 },
        { pct: '25%',  color: '#ef4444',      label: REG_STR.strength_1 },
        { pct: '50%',  color: '#f97316',      label: REG_STR.strength_2 },
        { pct: '75%',  color: '#f59e0b',      label: REG_STR.strength_3 },
        { pct: '90%',  color: '#10b981',      label: REG_STR.strength_4 },
        { pct: '100%', color: '#059669',      label: REG_STR.strength_5 },
    ];
    const lvl = val.length === 0 ? levels[0] : levels[Math.min(score, 5)];
    bar.style.width = lvl.pct;
    bar.style.background = lvl.color;
    text.textContent = lvl.label;
    text.style.color = lvl.color === 'transparent' ? '#94a3b8' : lvl.color;
}

// ── 회사 필드 필수 검증 ──
document.getElementById('register-form').addEventListener('submit', function(e) {
    const company = document.getElementById('company-value').value.trim();
    if (!company) {
        e.preventDefault();
        const input = document.getElementById('company-input');
        input.style.borderColor = '#fca5a5';
        input.focus();
        input.placeholder = REG_STR.company_required;
        setTimeout(() => {
            input.style.borderColor = '';
            input.placeholder = REG_STR.company_placeholder;
        }, 3000);
    }
});

// ── Input focus icon color ──
document.querySelectorAll('.form-input').forEach(function(input) {
    input.addEventListener('focus', function() {
        var wrap = input.closest('.input-wrap');
        var icon = wrap ? wrap.querySelector('.input-icon') : null;
        if (icon) icon.style.color = '#6366f1';
    });
    input.addEventListener('blur', function() {
        var wrap = input.closest('.input-wrap');
        var icon = wrap ? wrap.querySelector('.input-icon') : null;
        if (icon) icon.style.color = '';
    });
});

// ── 회사 자동완성 콤보박스 ──
(function () {
    const searchInput  = document.getElementById('company-input');
    const hiddenInput  = document.getElementById('company-value');
    const dropdown     = document.getElementById('combo-dropdown');
    const list         = document.getElementById('combo-list');
    const clearBtn     = document.getElementById('combo-clear');

    if (!searchInput) return;

    const AUTOCOMPLETE_URL = '{{ route('autocomplete.companies') }}';
    let focusIndex = -1;
    let debounceTimer = null;
    let lastQuery = '';
    let isOpen = false;

    // 초기값이 있을 때 X 버튼 표시
    if (searchInput.value.trim()) {
        hiddenInput.value = searchInput.value.trim();
        clearBtn.classList.add('visible');
    }

    function highlight(text, query) {
        if (!query) return escHtml(text);
        const re = new RegExp('(' + escRe(query) + ')', 'gi');
        return escHtml(text).replace(re, '<mark>$1</mark>');
    }
    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escRe(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function openDropdown() { dropdown.classList.add('open'); isOpen = true; }
    function closeDropdown() { dropdown.classList.remove('open'); isOpen = false; focusIndex = -1; }

    function buildList(items, query) {
        list.innerHTML = '';
        focusIndex = -1;

        if (items.length > 0) {
            items.forEach((company, idx) => {
                const el = document.createElement('div');
                el.className = 'combo-item';
                el.setAttribute('role', 'option');
                el.dataset.idx = idx;
                el.innerHTML = `
                    <span class="combo-item-icon">🏢</span>
                    <span class="combo-item-text">${highlight(company, query)}</span>
                    <span class="combo-item-badge">${REG_STR.combo_existing}</span>`;
                el.addEventListener('mousedown', e => {
                    e.preventDefault();
                    selectItem(company);
                });
                list.appendChild(el);
            });
        }

        // "직접 입력" 항목
        const trimmed = query.trim();
        const alreadyExact = items.some(c => c.toLowerCase() === trimmed.toLowerCase());
        if (trimmed && !alreadyExact) {
            const el = document.createElement('div');
            el.className = 'combo-new';
            el.setAttribute('role', 'option');
            el.dataset.idx = items.length;
            el.innerHTML = `<span class="combo-new-icon">✏️</span><span>"<strong>${escHtml(trimmed)}</strong>" ${REG_STR.combo_direct}</span>`;
            el.addEventListener('mousedown', e => {
                e.preventDefault();
                selectItem(trimmed);
            });
            list.appendChild(el);
        }

        if (list.children.length === 0) {
            list.innerHTML = `<div class="combo-empty">${REG_STR.combo_no_result}</div>`;
        }
    }

    function selectItem(value) {
        searchInput.value = value;
        hiddenInput.value = value;
        clearBtn.classList.add('visible');
        closeDropdown();
        searchInput.dispatchEvent(new Event('change'));
    }

    function showLoading() {
        list.innerHTML = `<div class="combo-loading"><span class="combo-spinner"></span> ${REG_STR.combo_searching}</div>`;
        openDropdown();
    }

    async function fetchSuggestions(query) {
        if (query === lastQuery) return;
        lastQuery = query;

        if (query.length < 1) { closeDropdown(); return; }

        showLoading();
        try {
            const res = await fetch(`${AUTOCOMPLETE_URL}?q=${encodeURIComponent(query)}`);
            if (!res.ok) throw new Error();
            const data = await res.json();
            buildList(data, query);
            openDropdown();
        } catch {
            list.innerHTML = `<div class="combo-empty">${REG_STR.combo_error}</div>`;
        }
    }

    searchInput.addEventListener('input', () => {
        const val = searchInput.value;
        hiddenInput.value = val;
        clearBtn.classList.toggle('visible', val.length > 0);

        clearTimeout(debounceTimer);
        if (val.trim().length === 0) { lastQuery = ''; closeDropdown(); return; }
        debounceTimer = setTimeout(() => fetchSuggestions(val.trim()), 250);
    });

    searchInput.addEventListener('focus', () => {
        const val = searchInput.value.trim();
        if (val.length > 0 && !isOpen) {
            lastQuery = '';
            fetchSuggestions(val);
        }
    });

    searchInput.addEventListener('keydown', e => {
        if (!isOpen) return;
        const items = list.querySelectorAll('.combo-item,.combo-new');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusIndex = Math.min(focusIndex + 1, items.length - 1);
            updateFocus(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            focusIndex = Math.max(focusIndex - 1, -1);
            updateFocus(items);
        } else if (e.key === 'Enter' && focusIndex >= 0) {
            e.preventDefault();
            items[focusIndex].dispatchEvent(new Event('mousedown'));
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    function updateFocus(items) {
        items.forEach((el, i) => el.classList.toggle('focused', i === focusIndex));
        if (focusIndex >= 0) items[focusIndex].scrollIntoView({ block: 'nearest' });
    }

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        hiddenInput.value = '';
        clearBtn.classList.remove('visible');
        lastQuery = '';
        closeDropdown();
        searchInput.focus();
    });

    document.addEventListener('mousedown', e => {
        if (!searchInput.closest('.combo-wrap').contains(e.target)) closeDropdown();
    });
})();
</script>
</body>
</html>
