<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('welcome.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        :root {
            --p1: #8b5cf6; --p2: #6d28d9; --p3: #4c1d95;
            --a1: #06b6d4; --a2: #0891b2;
            --g1: #10b981; --g2: #059669;
            --dark: #0f0a1e; --dark2: #1a1040; --dark3: #241562;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Inter','Noto Sans KR',sans-serif;background:var(--dark);color:#fff;overflow-x:hidden}

        /* ─── NAV ─── */
        .nav{
            position:fixed;top:0;left:0;right:0;z-index:200;
            display:flex;align-items:center;justify-content:space-between;
            padding:0 2.5rem;height:68px;
            background:rgba(15,10,30,.6);backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(139,92,246,.15);
        }
        .nav-logo{font-size:1.2rem;font-weight:900;letter-spacing:-0.5px;
            background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .nav-links{display:flex;gap:2.25rem;align-items:center}
        .nav-links a{color:rgba(255,255,255,.65);font-size:.9rem;font-weight:500;text-decoration:none;transition:color .2s}
        .nav-links a:hover{color:#fff}
        .nav-login{display:inline-flex;align-items:center;gap:.4rem;
            background:transparent;color:rgba(255,255,255,.75);
            border:1px solid rgba(139,92,246,.45);border-radius:8px;
            padding:.5rem 1.25rem;font-size:.875rem;font-weight:600;text-decoration:none;
            transition:all .2s}
        .nav-login:hover{color:#fff;border-color:rgba(167,139,250,.8);background:rgba(139,92,246,.12)}
        .nav-cta{display:inline-flex;align-items:center;gap:.4rem;
            background:linear-gradient(135deg,var(--p1),var(--p2));color:#fff;border:none;border-radius:8px;
            padding:.5rem 1.25rem;font-size:.875rem;font-weight:700;text-decoration:none;
            box-shadow:0 0 20px rgba(139,92,246,.4);transition:all .2s}
        .nav-cta:hover{transform:translateY(-2px);box-shadow:0 0 30px rgba(139,92,246,.6)}

        /* ─── HERO ─── */
        .hero{
            position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;
            overflow:hidden;padding:100px 1.5rem 4rem;
        }
        #hero-canvas{position:absolute;inset:0;z-index:0}
        .hero-glow-1{position:absolute;top:-200px;left:-200px;width:700px;height:700px;border-radius:50%;
            background:radial-gradient(circle,rgba(109,40,217,.4) 0%,transparent 70%);z-index:0;pointer-events:none}
        .hero-glow-2{position:absolute;bottom:-200px;right:-200px;width:600px;height:600px;border-radius:50%;
            background:radial-gradient(circle,rgba(6,182,212,.3) 0%,transparent 70%);z-index:0;pointer-events:none}
        .hero-inner{position:relative;z-index:1;display:flex;gap:5rem;align-items:center;max-width:1200px;width:100%;flex-wrap:wrap;justify-content:center}
        .hero-text{flex:1;min-width:300px;max-width:600px}
        .hero-badge{
            display:inline-flex;align-items:center;gap:.5rem;
            background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.4);
            border-radius:999px;padding:.3rem 1rem;font-size:.78rem;font-weight:600;
            color:#c4b5fd;margin-bottom:1.75rem;letter-spacing:.3px;
        }
        .badge-dot{width:6px;height:6px;border-radius:50%;background:#a78bfa;animation:blink 1.6s infinite}
        @keyframes blink{0%,100%{box-shadow:0 0 0 0 rgba(167,139,250,.8)}70%{box-shadow:0 0 0 6px rgba(167,139,250,0)}}
        .hero h1{
            font-size:clamp(2.6rem,5.5vw,4.2rem);font-weight:900;line-height:1.1;letter-spacing:-2px;
            margin-bottom:1.5rem;
        }
        .hero h1 .line1{display:block;color:#fff}
        .hero h1 .line2{display:block;
            background:linear-gradient(90deg,#a78bfa 0%,#67e8f9 50%,#34d399 100%);
            -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;
        }
        .hero-desc{font-size:1.1rem;color:rgba(255,255,255,.6);line-height:1.8;margin-bottom:2.5rem;max-width:500px}
        .hero-btns{display:flex;gap:1rem;flex-wrap:wrap}
        .btn-glow{
            display:inline-flex;align-items:center;gap:.5rem;
            background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;border:none;border-radius:10px;
            padding:.9rem 2rem;font-size:.975rem;font-weight:700;text-decoration:none;
            box-shadow:0 0 30px rgba(139,92,246,.5),0 4px 15px rgba(0,0,0,.3);
            transition:all .25s;
        }
        .btn-glow:hover{transform:translateY(-3px);box-shadow:0 0 50px rgba(139,92,246,.7),0 8px 25px rgba(0,0,0,.4)}
        .btn-ghost{
            display:inline-flex;align-items:center;gap:.5rem;
            background:rgba(255,255,255,.06);color:#e0e7ff;
            border:1px solid rgba(255,255,255,.15);border-radius:10px;
            padding:.9rem 2rem;font-size:.975rem;font-weight:600;text-decoration:none;
            backdrop-filter:blur(8px);transition:all .25s;
        }
        .btn-ghost:hover{background:rgba(255,255,255,.1);border-color:rgba(167,139,250,.4);transform:translateY(-2px)}
        .hero-stats{display:flex;gap:2.5rem;margin-top:3rem;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1)}
        .hero-stat-num{font-size:1.75rem;font-weight:900;
            background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .hero-stat-lbl{font-size:.78rem;color:rgba(255,255,255,.45);margin-top:2px}

        /* 웍스 VISUAL */
        .hero-visual{flex:1;min-width:300px;max-width:520px;position:relative}
        .ai-orb{
            width:100%;aspect-ratio:1;border-radius:50%;
            background:radial-gradient(circle at 35% 35%,rgba(167,139,250,.25) 0%,rgba(109,40,217,.15) 40%,transparent 70%);
            border:1px solid rgba(139,92,246,.2);
            position:relative;display:flex;align-items:center;justify-content:center;
        }
        .ai-orb::before{
            content:'';position:absolute;inset:-2px;border-radius:50%;
            background:conic-gradient(from 0deg,transparent 0%,rgba(139,92,246,.6) 20%,transparent 40%,rgba(6,182,212,.4) 60%,transparent 80%,rgba(52,211,153,.4) 100%);
            animation:spin 8s linear infinite;z-index:-1;
            -webkit-mask:radial-gradient(circle,transparent 88%,black 100%);
            mask:radial-gradient(circle,transparent 88%,black 100%);
        }
        .ai-orb::after{
            content:'';position:absolute;inset:0;border-radius:50%;
            background:radial-gradient(circle at 50% 50%,rgba(139,92,246,.08) 0%,transparent 70%);
        }
        @keyframes spin{to{transform:rotate(360deg)}}
        .ai-brain-svg{width:65%;height:65%;position:relative;z-index:1;filter:drop-shadow(0 0 30px rgba(139,92,246,.6))}
        .orb-ring{
            position:absolute;border-radius:50%;border:1px solid;animation:ripple 3s ease-out infinite;
        }
        .orb-ring:nth-child(1){inset:-20px;border-color:rgba(139,92,246,.3);animation-delay:0s}
        .orb-ring:nth-child(2){inset:-50px;border-color:rgba(6,182,212,.2);animation-delay:1s}
        .orb-ring:nth-child(3){inset:-85px;border-color:rgba(52,211,153,.1);animation-delay:2s}
        @keyframes ripple{0%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(1.1)}}

        .float-card{
            position:absolute;background:rgba(255,255,255,.07);backdrop-filter:blur(16px);
            border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:.85rem 1.1rem;
            display:flex;align-items:center;gap:.7rem;font-size:.78rem;white-space:nowrap;
            box-shadow:0 8px 32px rgba(0,0,0,.3);animation:float 4s ease-in-out infinite;
        }
        .float-card:nth-of-type(1){top:8%;right:-5%;animation-delay:0s}
        .float-card:nth-of-type(2){bottom:18%;left:-8%;animation-delay:1.5s}
        .float-card:nth-of-type(3){top:40%;left:-10%;animation-delay:.8s}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        .float-icon{font-size:1.2rem}
        .float-text{color:rgba(255,255,255,.9);font-weight:600}
        .float-sub{color:rgba(255,255,255,.45);font-size:.7rem}

        /* ─── TECH STACK SECTION ─── */
        .tech-section{
            padding:4rem 2rem;
            border-top:1px solid rgba(255,255,255,.05);
            border-bottom:1px solid rgba(255,255,255,.05);
            background:linear-gradient(180deg,rgba(139,92,246,.03) 0%,transparent 100%);
        }
        .tech-header{text-align:center;margin-bottom:2.75rem}
        .tech-eyebrow{display:inline-block;font-size:.68rem;font-weight:800;letter-spacing:3px;color:rgba(139,92,246,.55);text-transform:uppercase;margin-bottom:.6rem}
        .tech-headline{font-size:1.15rem;font-weight:700;color:rgba(255,255,255,.55);letter-spacing:-.3px}
        .tech-grid{
            display:flex;flex-wrap:wrap;justify-content:center;gap:1rem;
            max-width:1040px;margin:0 auto;
        }
        .tech-card{
            width:114px;
            background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.07);
            border-radius:18px;padding:1.4rem .75rem 1.1rem;
            display:flex;flex-direction:column;align-items:center;gap:.6rem;
            transition:all .3s;text-align:center;cursor:default;
        }
        .tech-card:hover{
            background:rgba(255,255,255,.08);
            transform:translateY(-5px);
            box-shadow:0 14px 32px rgba(0,0,0,.35);
        }
        .tech-icon{
            width:46px;height:46px;border-radius:13px;
            display:flex;align-items:center;justify-content:center;
            flex-shrink:0;
        }
        .tech-name{font-size:.72rem;font-weight:700;color:rgba(255,255,255,.55);letter-spacing:.2px;line-height:1.3}
        .tech-role{font-size:.6rem;color:rgba(255,255,255,.22);margin-top:.1rem}

        /* ─── SECTION COMMON ─── */
        .section{padding:7rem 1.5rem;position:relative}
        .s-inner{max-width:1150px;margin:0 auto}
        .s-label{display:inline-flex;align-items:center;gap:.5rem;
            color:#a78bfa;font-size:.75rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:1rem}
        .s-label::before{content:'';width:20px;height:2px;background:#8b5cf6;border-radius:1px}
        .s-title{font-size:clamp(1.9rem,3.5vw,2.8rem);font-weight:900;letter-spacing:-1.5px;line-height:1.15;margin-bottom:1.1rem}
        .s-desc{font-size:1rem;color:rgba(255,255,255,.5);max-width:540px;line-height:1.75}
        .text-c{text-align:center}.text-c .s-desc{margin:0 auto}.text-c .s-label{margin:0 auto 1rem}

        /* ─── FEATURES ─── */
        .features-bg{background:linear-gradient(180deg,var(--dark) 0%,var(--dark2) 100%)}
        .features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem;margin-top:4rem}
        .feat-card{
            background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:20px;
            padding:2.25rem;position:relative;overflow:hidden;transition:all .3s;cursor:default;
        }
        .feat-card::before{
            content:'';position:absolute;inset:0;border-radius:20px;opacity:0;
            background:radial-gradient(circle at var(--mx,50%) var(--my,50%),rgba(139,92,246,.12),transparent 60%);
            transition:opacity .3s;
        }
        .feat-card:hover::before{opacity:1}
        .feat-card:hover{border-color:rgba(139,92,246,.3);transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,.4)}
        .feat-icon-wrap{
            width:56px;height:56px;border-radius:14px;margin-bottom:1.5rem;
            display:flex;align-items:center;justify-content:center;font-size:1.5rem;
        }
        .feat-card h3{font-size:1.05rem;font-weight:800;margin-bottom:.65rem;color:#f0eeff}
        .feat-card p{font-size:.875rem;color:rgba(255,255,255,.5);line-height:1.7}
        .feat-tag{
            display:inline-flex;align-items:center;gap:.3rem;
            background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.25);
            border-radius:999px;padding:.2rem .65rem;font-size:.7rem;color:#c4b5fd;font-weight:600;
            margin-top:1.25rem;
        }

        /* ─── 웍스 SHOWCASE ─── */
        .ai-showcase{background:var(--dark2);overflow:hidden}
        .ai-showcase-inner{max-width:1150px;margin:0 auto;display:flex;gap:5rem;align-items:center;flex-wrap:wrap}
        .ai-showcase-text{flex:1;min-width:280px}
        .ai-showcase-text .s-title{background:linear-gradient(90deg,#f0eeff,#a78bfa);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent}
        .ai-features-list{list-style:none;margin:2rem 0;display:flex;flex-direction:column;gap:1.1rem}
        .ai-features-list li{display:flex;align-items:flex-start;gap:1rem;font-size:.9rem;color:rgba(255,255,255,.7);line-height:1.6}
        .ai-feat-icon{
            width:32px;height:32px;border-radius:9px;flex-shrink:0;
            background:linear-gradient(135deg,rgba(139,92,246,.3),rgba(6,182,212,.2));
            border:1px solid rgba(139,92,246,.3);
            display:flex;align-items:center;justify-content:center;font-size:.9rem;margin-top:1px;
        }
        .ai-feat-strong{color:#e0d9ff;font-weight:700}

        .ai-visual-panel{flex:1;min-width:320px;max-width:500px}
        .ai-screen{
            background:rgba(15,10,30,.8);border:1px solid rgba(139,92,246,.25);border-radius:20px;
            overflow:hidden;box-shadow:0 0 80px rgba(109,40,217,.3),0 40px 80px rgba(0,0,0,.5);
        }
        .ai-screen-bar{
            background:rgba(255,255,255,.04);padding:.9rem 1.25rem;
            display:flex;align-items:center;gap:.75rem;border-bottom:1px solid rgba(255,255,255,.07);
        }
        .scr-dot{width:9px;height:9px;border-radius:50%}
        .ai-screen-body{padding:1.5rem;display:flex;flex-direction:column;gap:1rem}
        .ai-msg{display:flex;gap:.75rem;align-items:flex-start}
        .ai-msg.user{flex-direction:row-reverse}
        .ai-avatar{
            width:32px;height:32px;border-radius:9px;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;font-size:.85rem;
        }
        .ai-avatar.bot{background:linear-gradient(135deg,#8b5cf6,#06b6d4)}
        .ai-avatar.usr{background:linear-gradient(135deg,#10b981,#0891b2)}
        .ai-bubble{
            max-width:80%;padding:.75rem 1rem;border-radius:12px;font-size:.8rem;line-height:1.6;
        }
        .ai-bubble.bot{background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.2);color:#e0d9ff;border-top-left-radius:4px}
        .ai-bubble.usr{background:linear-gradient(135deg,rgba(139,92,246,.4),rgba(109,40,217,.4));color:#f0eeff;border-top-right-radius:4px}
        .ai-typing{display:flex;align-items:center;gap:4px;padding:.75rem 1rem}
        .t-dot{width:5px;height:5px;border-radius:50%;background:#a78bfa;animation:bounce 1.2s infinite}
        .t-dot:nth-child(2){animation-delay:.2s}.t-dot:nth-child(3){animation-delay:.4s}
        @keyframes bounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-6px)}}

        /* 웍스 IMAGE SECTION */
        .ai-image-section{
            background:linear-gradient(180deg,var(--dark2) 0%,var(--dark3) 50%,var(--dark2) 100%);
            padding:7rem 1.5rem;
        }
        .ai-image-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:4rem}
        @media(max-width:900px){.ai-image-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:600px){.ai-image-grid{grid-template-columns:1fr}}
        .ai-img-card{
            border-radius:20px;overflow:hidden;position:relative;aspect-ratio:4/3;
            border:1px solid rgba(255,255,255,.08);transition:transform .3s,box-shadow .3s;cursor:pointer;
        }
        .ai-img-card:hover{transform:translateY(-6px) scale(1.01);box-shadow:0 30px 60px rgba(0,0,0,.5)}
        .ai-img-card:first-child{grid-row:span 2;aspect-ratio:auto}
        .ai-img-svg{width:100%;height:100%;display:block}
        .ai-img-label{
            position:absolute;bottom:0;left:0;right:0;
            padding:1rem 1.25rem;
            background:linear-gradient(to top,rgba(15,10,30,.9) 0%,transparent 100%);
        }
        .ai-img-label h4{font-size:.875rem;font-weight:700;margin-bottom:.25rem}
        .ai-img-label p{font-size:.73rem;color:rgba(255,255,255,.5)}

        /* ─── WORKFLOW ─── */
        .workflow-bg{background:var(--dark)}
        .workflow-timeline{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:0;margin-top:4rem;position:relative}
        .workflow-timeline::before{
            content:'';position:absolute;top:56px;left:10%;right:10%;height:2px;
            background:linear-gradient(90deg,var(--p1),var(--a1),var(--g1));z-index:0;
            display:none;
        }
        .wf-step{
            text-align:center;padding:2.5rem 1.5rem;position:relative;z-index:1;
            transition:transform .25s;
        }
        .wf-step:hover{transform:translateY(-6px)}
        .wf-num{
            width:52px;height:52px;border-radius:50%;margin:0 auto 1.5rem;
            display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900;
            position:relative;
        }
        .wf-num::before{
            content:'';position:absolute;inset:-3px;border-radius:50%;
            background:conic-gradient(from 0deg,var(--p1),var(--a1),var(--g1),var(--p1));
            z-index:-1;animation:spin 4s linear infinite;
        }
        .wf-num-inner{width:100%;height:100%;border-radius:50%;background:var(--dark);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900}
        .wf-step h3{font-size:1rem;font-weight:800;margin-bottom:.6rem;color:#e0d9ff}
        .wf-step p{font-size:.85rem;color:rgba(255,255,255,.45);line-height:1.65}
        .wf-icon{font-size:1.8rem;margin-bottom:.75rem}

        /* ─── TESTIMONIALS ─── */
        .testi-bg{background:var(--dark2)}
        .testi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;margin-top:4rem}
        .testi-card{
            background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.07);border-radius:20px;
            padding:2rem;transition:all .3s;
        }
        .testi-card:hover{border-color:rgba(139,92,246,.3);transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,.3)}
        .testi-stars{color:#fbbf24;font-size:.85rem;letter-spacing:3px;margin-bottom:1.1rem}
        .testi-card blockquote{font-size:.875rem;color:rgba(255,255,255,.65);line-height:1.7;margin:0 0 1.5rem;font-style:italic}
        .testi-author{display:flex;align-items:center;gap:.85rem}
        .testi-avatar{
            width:40px;height:40px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;font-size:.875rem;font-weight:800;color:#fff;flex-shrink:0;
        }
        .testi-name{font-weight:700;font-size:.875rem;color:#f0eeff}
        .testi-role{font-size:.75rem;color:rgba(255,255,255,.4);margin-top:2px}

        /* ─── CTA ─── */
        .cta-section{
            position:relative;overflow:hidden;padding:8rem 1.5rem;text-align:center;
            background:linear-gradient(135deg,var(--dark3) 0%,#1e0a5e 50%,var(--dark3) 100%);
        }
        .cta-section::before{
            content:'';position:absolute;top:-300px;left:50%;transform:translateX(-50%);
            width:900px;height:600px;border-radius:50%;
            background:radial-gradient(circle,rgba(139,92,246,.3) 0%,transparent 70%);
            pointer-events:none;
        }
        .cta-section h2{
            font-size:clamp(2rem,5vw,3.5rem);font-weight:900;letter-spacing:-2px;margin-bottom:1.25rem;
            background:linear-gradient(90deg,#fff 30%,#c4b5fd 70%,#67e8f9 100%);
            -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;
        }
        .cta-section p{font-size:1.05rem;color:rgba(255,255,255,.55);max-width:480px;margin:0 auto 2.75rem;line-height:1.75}
        .btn-cta-main{
            display:inline-flex;align-items:center;gap:.6rem;
            background:linear-gradient(135deg,#8b5cf6 0%,#6d28d9 50%,#4c1d95 100%);
            color:#fff;border:none;border-radius:12px;
            padding:1rem 2.5rem;font-size:1.05rem;font-weight:800;text-decoration:none;
            box-shadow:0 0 50px rgba(139,92,246,.6),0 8px 30px rgba(0,0,0,.4);
            transition:all .25s;position:relative;z-index:1;
        }
        .btn-cta-main:hover{transform:translateY(-3px);box-shadow:0 0 80px rgba(139,92,246,.8),0 15px 40px rgba(0,0,0,.5)}
        .cta-note{font-size:.8rem;color:rgba(255,255,255,.3);margin-top:1rem}

        /* ─── FOOTER ─── */
        .footer{background:rgba(0,0,0,.45);border-top:1px solid rgba(255,255,255,.07);padding:0 1.5rem;}
        .footer-main{max-width:1150px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1.4fr;gap:3rem;padding:3.5rem 0 2.5rem;}
        @media(max-width:860px){.footer-main{grid-template-columns:1fr 1fr;gap:2rem;padding:2.5rem 0 2rem;}}
        @media(max-width:520px){.footer-main{grid-template-columns:1fr;gap:1.75rem;}}
        .footer-brand-name{font-size:1.1rem;font-weight:900;background:linear-gradient(90deg,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:.6rem;}
        .footer-brand-desc{font-size:.78rem;color:rgba(255,255,255,.35);line-height:1.75;margin-bottom:1.2rem;}
        .footer-biz{font-size:.72rem;color:rgba(255,255,255,.22);line-height:1.9;}
        .footer-col-title{font-size:.72rem;font-weight:700;color:rgba(255,255,255,.5);letter-spacing:.08em;text-transform:uppercase;margin-bottom:.9rem;}
        .footer-col ul{list-style:none;}
        .footer-col ul li{margin-bottom:.55rem;}
        .footer-col ul li a{font-size:.8rem;color:rgba(255,255,255,.32);text-decoration:none;transition:color .2s;}
        .footer-col ul li a:hover{color:rgba(255,255,255,.75);}
        .footer-contact-item{display:flex;align-items:flex-start;gap:.55rem;margin-bottom:.7rem;}
        .footer-contact-item svg{flex-shrink:0;margin-top:.1rem;opacity:.45;}
        .footer-contact-item p{font-size:.78rem;color:rgba(255,255,255,.32);line-height:1.55;}
        .footer-contact-item a{color:rgba(255,255,255,.42);text-decoration:none;}
        .footer-contact-item a:hover{color:rgba(255,255,255,.75);}
        .footer-bottom{max-width:1150px;margin:0 auto;border-top:1px solid rgba(255,255,255,.06);padding:1.2rem 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;}
        .footer-copy{font-size:.72rem;color:rgba(255,255,255,.2);}
        .footer-bottom-links{display:flex;gap:1.5rem;}
        .footer-bottom-links a{font-size:.72rem;color:rgba(255,255,255,.22);text-decoration:none;}
        .footer-bottom-links a:hover{color:rgba(255,255,255,.55);}

        /* ─── REVEAL ─── */
        .reveal{opacity:0;transform:translateY(30px);transition:opacity .7s ease,transform .7s ease}
        .reveal.visible{opacity:1;transform:translateY(0)}

        @media(max-width:768px){
            .nav-links{display:none}
            .hero-inner{gap:3rem}
            .ai-showcase-inner{flex-direction:column;gap:3rem}
            .ai-image-grid .ai-img-card:first-child{grid-row:auto;aspect-ratio:4/3}
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
    <div class="nav-logo">⚡ SupportWorks</div>
    <div class="nav-links">
        <a href="#features">{{ __('welcome.nav_features') }}</a>
        <a href="#ai">{{ __('welcome.nav_ai') }}</a>
        <a href="#workflow">{{ __('welcome.nav_workflow') }}</a>
        <a href="#testimonials">{{ __('welcome.nav_testimonials') }}</a>
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;">
        <div style="display:flex;gap:4px;">
            <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
                @csrf
                <input type="hidden" name="locale" value="ko">
                <button type="submit" style="padding:3px 9px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid rgba(255,255,255,.2);background:{{ app()->getLocale()==='ko' ? 'rgba(139,92,246,.35)' : 'rgba(255,255,255,.06)' }};color:{{ app()->getLocale()==='ko' ? '#c4b5fd' : 'rgba(255,255,255,.4)' }};cursor:pointer;font-family:inherit;transition:all .15s;">KO</button>
            </form>
            <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
                @csrf
                <input type="hidden" name="locale" value="en">
                <button type="submit" style="padding:3px 9px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid rgba(255,255,255,.2);background:{{ app()->getLocale()==='en' ? 'rgba(139,92,246,.35)' : 'rgba(255,255,255,.06)' }};color:{{ app()->getLocale()==='en' ? '#c4b5fd' : 'rgba(255,255,255,.4)' }};cursor:pointer;font-family:inherit;transition:all .15s;">EN</button>
            </form>
        </div>
        <div style="width:1px;height:16px;background:rgba(255,255,255,.15);margin:0 .5rem;"></div>
        <a href="{{ route('login') }}" class="nav-login">{{ __('welcome.nav_login') }}</a>
        <a href="{{ route('register') }}" class="nav-cta">{{ __('welcome.nav_cta') }}</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <canvas id="hero-canvas"></canvas>
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>
    <div class="hero-inner">
        <div class="hero-text">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                {{ __('welcome.hero_badge') }}
            </div>
            <h1>
                <span class="line1">{{ __('welcome.hero_title_line1') }}</span>
                <span class="line2">{{ __('welcome.hero_title_line2') }}</span>
            </h1>
            <p class="hero-desc">{{ __('welcome.hero_desc') }}</p>
            <div class="hero-btns">
                <a href="{{ route('register') }}" class="btn-glow">{{ __('welcome.hero_btn_start') }}</a>
                <a href="#features" class="btn-ghost">{{ __('welcome.hero_btn_features') }}</a>
            </div>
            <div class="hero-stats">
                <div>
                    <div class="hero-stat-num">{{ __('welcome.stat_productivity_num') }}</div>
                    <div class="hero-stat-lbl">{{ __('welcome.stat_productivity_label') }}</div>
                </div>
                <div>
                    <div class="hero-stat-num">{{ __('welcome.stat_setup_num') }}</div>
                    <div class="hero-stat-lbl">{{ __('welcome.stat_setup_label') }}</div>
                </div>
                <div>
                    <div class="hero-stat-num">{{ __('welcome.stat_support_num') }}</div>
                    <div class="hero-stat-lbl">{{ __('welcome.stat_support_label') }}</div>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="ai-orb">
                <div class="orb-ring"></div>
                <div class="orb-ring"></div>
                <div class="orb-ring"></div>
                <!-- 웍스 Brain SVG -->
                <svg class="ai-brain-svg" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <radialGradient id="bg1" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="#4c1d95" stop-opacity="0.05"/>
                        </radialGradient>
                        <filter id="glow">
                            <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                            <feMerge><feMergeNode in="coloredBlur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <linearGradient id="stroke1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#a78bfa"/>
                            <stop offset="100%" stop-color="#67e8f9"/>
                        </linearGradient>
                        <linearGradient id="stroke2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#34d399"/>
                            <stop offset="100%" stop-color="#a78bfa"/>
                        </linearGradient>
                    </defs>
                    <!-- Neural network nodes -->
                    <g filter="url(#glow)">
                        <!-- Input layer -->
                        <circle cx="35" cy="60" r="8" fill="none" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="35" cy="90" r="8" fill="none" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="35" cy="120" r="8" fill="none" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="35" cy="150" r="8" fill="none" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <!-- Hidden layer 1 -->
                        <circle cx="80" cy="50" r="9" fill="rgba(139,92,246,0.2)" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="80" cy="80" r="9" fill="rgba(139,92,246,0.2)" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="80" cy="110" r="9" fill="rgba(139,92,246,0.2)" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="80" cy="140" r="9" fill="rgba(139,92,246,0.2)" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <circle cx="80" cy="165" r="9" fill="rgba(139,92,246,0.2)" stroke="url(#stroke1)" stroke-width="1.5"/>
                        <!-- Hidden layer 2 -->
                        <circle cx="130" cy="65" r="10" fill="rgba(6,182,212,0.2)" stroke="url(#stroke2)" stroke-width="2"/>
                        <circle cx="130" cy="100" r="10" fill="rgba(6,182,212,0.2)" stroke="url(#stroke2)" stroke-width="2"/>
                        <circle cx="130" cy="135" r="10" fill="rgba(6,182,212,0.2)" stroke="url(#stroke2)" stroke-width="2"/>
                        <!-- Output layer -->
                        <circle cx="175" cy="80" r="11" fill="rgba(52,211,153,0.3)" stroke="#34d399" stroke-width="2"/>
                        <circle cx="175" cy="115" r="11" fill="rgba(52,211,153,0.3)" stroke="#34d399" stroke-width="2"/>
                        <!-- Inner dots -->
                        <circle cx="35" cy="60" r="3" fill="#a78bfa"/>
                        <circle cx="35" cy="90" r="3" fill="#a78bfa"/>
                        <circle cx="35" cy="120" r="3" fill="#a78bfa"/>
                        <circle cx="35" cy="150" r="3" fill="#a78bfa"/>
                        <circle cx="80" cy="50" r="4" fill="#c4b5fd"/>
                        <circle cx="80" cy="80" r="4" fill="#c4b5fd"/>
                        <circle cx="80" cy="110" r="4" fill="#c4b5fd"/>
                        <circle cx="80" cy="140" r="4" fill="#c4b5fd"/>
                        <circle cx="80" cy="165" r="4" fill="#c4b5fd"/>
                        <circle cx="130" cy="65" r="5" fill="#67e8f9"/>
                        <circle cx="130" cy="100" r="5" fill="#67e8f9"/>
                        <circle cx="130" cy="135" r="5" fill="#67e8f9"/>
                        <circle cx="175" cy="80" r="5" fill="#34d399"/>
                        <circle cx="175" cy="115" r="5" fill="#34d399"/>
                        <!-- Connections input->h1 -->
                        <line x1="43" y1="60" x2="71" y2="51" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.4"/>
                        <line x1="43" y1="60" x2="71" y2="80" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.3"/>
                        <line x1="43" y1="90" x2="71" y2="80" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.4"/>
                        <line x1="43" y1="90" x2="71" y2="110" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.3"/>
                        <line x1="43" y1="120" x2="71" y2="110" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.4"/>
                        <line x1="43" y1="120" x2="71" y2="140" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.3"/>
                        <line x1="43" y1="150" x2="71" y2="140" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.4"/>
                        <line x1="43" y1="150" x2="71" y2="165" stroke="#a78bfa" stroke-width="0.7" stroke-opacity="0.3"/>
                        <!-- Connections h1->h2 -->
                        <line x1="89" y1="50" x2="120" y2="65" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.5"/>
                        <line x1="89" y1="80" x2="120" y2="65" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.4"/>
                        <line x1="89" y1="80" x2="120" y2="100" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.5"/>
                        <line x1="89" y1="110" x2="120" y2="100" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.4"/>
                        <line x1="89" y1="110" x2="120" y2="135" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.5"/>
                        <line x1="89" y1="140" x2="120" y2="135" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.4"/>
                        <line x1="89" y1="165" x2="120" y2="135" stroke="#c4b5fd" stroke-width="0.8" stroke-opacity="0.3"/>
                        <!-- Connections h2->output -->
                        <line x1="140" y1="65" x2="164" y2="80" stroke="#67e8f9" stroke-width="1.2" stroke-opacity="0.6"/>
                        <line x1="140" y1="100" x2="164" y2="80" stroke="#67e8f9" stroke-width="1.2" stroke-opacity="0.5"/>
                        <line x1="140" y1="100" x2="164" y2="115" stroke="#67e8f9" stroke-width="1.2" stroke-opacity="0.6"/>
                        <line x1="140" y1="135" x2="164" y2="115" stroke="#67e8f9" stroke-width="1.2" stroke-opacity="0.5"/>
                    </g>
                    <!-- Animated pulse circles -->
                    <circle cx="175" cy="80" r="15" fill="none" stroke="#34d399" stroke-width="1" stroke-opacity="0.5">
                        <animate attributeName="r" from="11" to="22" dur="2s" repeatCount="indefinite"/>
                        <animate attributeName="stroke-opacity" from="0.5" to="0" dur="2s" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="175" cy="115" r="15" fill="none" stroke="#34d399" stroke-width="1" stroke-opacity="0.5">
                        <animate attributeName="r" from="11" to="22" dur="2.5s" repeatCount="indefinite"/>
                        <animate attributeName="stroke-opacity" from="0.5" to="0" dur="2.5s" repeatCount="indefinite"/>
                    </circle>
                </svg>
            </div>
            <!-- Float cards -->
            <div class="float-card">
                <div class="float-icon">🎯</div>
                <div>
                    <div class="float-text">{{ __('welcome.float_task_title') }}</div>
                    <div class="float-sub">{{ __('welcome.float_task_sub') }}</div>
                </div>
            </div>
            <div class="float-card">
                <div class="float-icon">📊</div>
                <div>
                    <div class="float-text">{{ __('welcome.float_prod_title') }}</div>
                    <div class="float-sub">{{ __('welcome.float_prod_sub') }}</div>
                </div>
            </div>
            <div class="float-card">
                <div class="float-icon">💬</div>
                <div>
                    <div class="float-text">{{ __('welcome.float_chat_title') }}</div>
                    <div class="float-sub">{{ __('welcome.float_chat_sub') }}</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CORE CAPABILITIES -->
<section class="tech-section">
    <div class="tech-header">
        <div class="tech-eyebrow">Core Capabilities</div>
        <p class="tech-headline">{{ __('welcome.tech_headline') }}</p>
    </div>
    <div class="tech-grid">

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(139,92,246,.18);color:#a78bfa">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                    <path d="M14 17.5 L17.5 21 L22 15" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_pm_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_pm_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(6,182,212,.15);color:#22d3ee">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="9" y1="10" x2="15" y2="10" stroke-linecap="round"/>
                    <line x1="9" y1="13" x2="13" y2="13" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_chat_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_chat_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(16,185,129,.15);color:#34d399">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="12" cy="5" r="2.5"/>
                    <circle cx="5" cy="18" r="2.5"/>
                    <circle cx="19" cy="18" r="2.5"/>
                    <line x1="12" y1="7.5" x2="5.8" y2="15.8" stroke-linecap="round"/>
                    <line x1="12" y1="7.5" x2="18.2" y2="15.8" stroke-linecap="round"/>
                    <line x1="7.2" y1="18" x2="16.8" y2="18" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_ai_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_ai_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(249,115,22,.15);color:#fb923c">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_analytics_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_analytics_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(34,211,238,.13);color:#67e8f9">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="3" y="4" width="18" height="18" rx="2" stroke-linejoin="round"/>
                    <line x1="16" y1="2" x2="16" y2="6" stroke-linecap="round"/>
                    <line x1="8" y1="2" x2="8" y2="6" stroke-linecap="round"/>
                    <line x1="3" y1="10" x2="21" y2="10" stroke-linecap="round"/>
                    <path d="M8 14 L11 17 L16 12" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_schedule_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_schedule_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(92,95,196,.2);color:#818cf8">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.73a16 16 0 0 0 6.29 6.29l1.62-1.62a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_support_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_support_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(236,72,153,.15);color:#f472b6">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linejoin="round"/>
                    <path d="M9 12 l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_security_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_security_role') }}</div>
        </div>

        <div class="tech-card">
            <div class="tech-icon" style="background:rgba(250,204,21,.13);color:#fde047">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="5" y="2" width="14" height="20" rx="2" stroke-linejoin="round"/>
                    <rect x="2" y="7" width="5" height="11" rx="1.5" stroke-linejoin="round"/>
                    <rect x="17" y="7" width="5" height="11" rx="1.5" stroke-linejoin="round"/>
                    <line x1="12" y1="18" x2="12" y2="18.5" stroke-linecap="round" stroke-width="2"/>
                </svg>
            </div>
            <div class="tech-name">{{ __('welcome.tech_multi_name') }}</div>
            <div class="tech-role">{{ __('welcome.tech_multi_role') }}</div>
        </div>

    </div>
</section>

<!-- 웍스 IMAGE SHOWCASE -->
<section class="ai-image-section">
    <div class="s-inner">
        <div class="text-c reveal">
            <div class="s-label">{{ __('welcome.img_section_label') }}</div>
            <h2 class="s-title" style="background:linear-gradient(90deg,#f0eeff,#a78bfa,#67e8f9);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;">
                {{ __('welcome.img_section_title') }}
            </h2>
            <p class="s-desc">{{ __('welcome.img_section_desc') }}</p>
        </div>
        <div class="ai-image-grid">
            <!-- Card 1: Large - 웍스 Dashboard -->
            <div class="ai-img-card reveal" style="transition-delay:.05s;">
                <svg class="ai-img-svg" viewBox="0 0 400 530" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g-card1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0f0a1e"/>
                            <stop offset="100%" stop-color="#1a1040"/>
                        </linearGradient>
                        <radialGradient id="rg1" cx="30%" cy="30%" r="60%">
                            <stop offset="0%" stop-color="rgba(139,92,246,0.3)"/>
                            <stop offset="100%" stop-color="transparent"/>
                        </radialGradient>
                    </defs>
                    <rect width="400" height="530" fill="url(#g-card1)"/>
                    <rect width="400" height="530" fill="url(#rg1)"/>
                    <!-- Header -->
                    <rect x="0" y="0" width="400" height="50" fill="rgba(255,255,255,0.03)"/>
                    <circle cx="20" cy="25" r="8" fill="#a78bfa" opacity="0.8"/>
                    <circle cx="44" cy="25" r="8" fill="#67e8f9" opacity="0.8"/>
                    <circle cx="68" cy="25" r="8" fill="#34d399" opacity="0.8"/>
                    <text x="110" y="30" fill="rgba(255,255,255,0.5)" font-size="12" font-family="Inter,sans-serif">웍스 Dashboard — SupportWorks</text>
                    <!-- Sidebar -->
                    <rect x="0" y="50" width="80" height="480" fill="rgba(255,255,255,0.02)"/>
                    <rect x="8" y="70" width="64" height="32" rx="8" fill="rgba(139,92,246,0.3)"/>
                    <rect x="8" y="112" width="64" height="32" rx="8" fill="rgba(255,255,255,0.04)"/>
                    <rect x="8" y="154" width="64" height="32" rx="8" fill="rgba(255,255,255,0.04)"/>
                    <rect x="8" y="196" width="64" height="32" rx="8" fill="rgba(255,255,255,0.04)"/>
                    <!-- Icons in sidebar -->
                    <text x="24" y="91" fill="#c4b5fd" font-size="14">📊</text>
                    <text x="24" y="133" fill="rgba(255,255,255,0.4)" font-size="14">📁</text>
                    <text x="24" y="175" fill="rgba(255,255,255,0.4)" font-size="14">💬</text>
                    <text x="24" y="217" fill="rgba(255,255,255,0.4)" font-size="14">🤖</text>
                    <!-- Stat cards -->
                    <rect x="95" y="60" width="90" height="80" rx="12" fill="rgba(139,92,246,0.15)" stroke="rgba(139,92,246,0.3)" stroke-width="1"/>
                    <text x="140" y="100" fill="#c4b5fd" font-size="24" font-weight="900" text-anchor="middle" font-family="Inter,sans-serif">12</text>
                    <text x="140" y="118" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="middle" font-family="Inter,sans-serif">{{ __('welcome.svg_in_progress') }}</text>
                    <rect x="195" y="60" width="90" height="80" rx="12" fill="rgba(6,182,212,0.15)" stroke="rgba(6,182,212,0.3)" stroke-width="1"/>
                    <text x="240" y="100" fill="#67e8f9" font-size="24" font-weight="900" text-anchor="middle" font-family="Inter,sans-serif">48</text>
                    <text x="240" y="118" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="middle" font-family="Inter,sans-serif">{{ __('welcome.svg_done') }}</text>
                    <rect x="295" y="60" width="90" height="80" rx="12" fill="rgba(52,211,153,0.15)" stroke="rgba(52,211,153,0.3)" stroke-width="1"/>
                    <text x="340" y="100" fill="#34d399" font-size="24" font-weight="900" text-anchor="middle" font-family="Inter,sans-serif">7</text>
                    <text x="340" y="118" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="middle" font-family="Inter,sans-serif">{{ __('welcome.svg_members') }}</text>
                    <!-- Bar chart -->
                    <rect x="95" y="155" width="290" height="160" rx="12" fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
                    <text x="110" y="175" fill="rgba(255,255,255,0.6)" font-size="10" font-family="Inter,sans-serif" font-weight="600">{{ __('welcome.svg_weekly_analysis') }}</text>
                    <!-- bars -->
                    <rect x="115" y="230" width="22" height="60" rx="4" fill="rgba(139,92,246,0.5)"/>
                    <rect x="145" y="210" width="22" height="80" rx="4" fill="rgba(139,92,246,0.7)"/>
                    <rect x="175" y="220" width="22" height="70" rx="4" fill="rgba(139,92,246,0.5)"/>
                    <rect x="205" y="195" width="22" height="95" rx="4" fill="rgba(6,182,212,0.7)"/>
                    <rect x="235" y="205" width="22" height="85" rx="4" fill="rgba(139,92,246,0.5)"/>
                    <rect x="265" y="185" width="22" height="105" rx="4" fill="rgba(52,211,153,0.8)"/>
                    <rect x="295" y="200" width="22" height="90" rx="4" fill="rgba(6,182,212,0.6)"/>
                    <text x="115" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_mon') }}</text>
                    <text x="145" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_tue') }}</text>
                    <text x="175" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_wed') }}</text>
                    <text x="205" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_thu') }}</text>
                    <text x="235" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_fri') }}</text>
                    <text x="265" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_sat') }}</text>
                    <text x="295" y="305" fill="rgba(255,255,255,0.25)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_sun') }}</text>
                    <!-- Tasks -->
                    <rect x="95" y="330" width="290" height="160" rx="12" fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
                    <text x="110" y="350" fill="rgba(255,255,255,0.6)" font-size="10" font-family="Inter,sans-serif" font-weight="600">{{ __('welcome.svg_active_tasks') }}</text>
                    <circle cx="112" cy="372" r="5" fill="#34d399"/>
                    <text x="122" y="376" fill="rgba(255,255,255,0.7)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_task1') }}</text>
                    <circle cx="112" cy="394" r="5" fill="#fbbf24"/>
                    <text x="122" y="398" fill="rgba(255,255,255,0.7)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_task2') }}</text>
                    <circle cx="112" cy="416" r="5" fill="#a78bfa"/>
                    <text x="122" y="420" fill="rgba(255,255,255,0.7)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_task3') }}</text>
                    <circle cx="112" cy="438" r="5" fill="#67e8f9"/>
                    <text x="122" y="442" fill="rgba(255,255,255,0.7)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_task4') }}</text>
                    <!-- 웍스 insight pill -->
                    <rect x="95" y="500" width="290" height="24" rx="12" fill="rgba(139,92,246,0.2)" stroke="rgba(139,92,246,0.4)" stroke-width="1"/>
                    <text x="200" y="516" fill="#c4b5fd" font-size="9" font-family="Inter,sans-serif" text-anchor="middle" font-weight="600">{{ __('welcome.svg_ai_insight') }}</text>
                </svg>
                <div class="ai-img-label">
                    <h4>{{ __('welcome.card1_title') }}</h4>
                    <p>{{ __('welcome.card1_desc') }}</p>
                </div>
            </div>
            <!-- Card 2: 웍스 Chat -->
            <div class="ai-img-card reveal" style="transition-delay:.1s;">
                <svg class="ai-img-svg" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g-card2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0a1628"/>
                            <stop offset="100%" stop-color="#0f1e35"/>
                        </linearGradient>
                        <radialGradient id="rg2" cx="80%" cy="20%" r="60%">
                            <stop offset="0%" stop-color="rgba(6,182,212,0.25)"/>
                            <stop offset="100%" stop-color="transparent"/>
                        </radialGradient>
                    </defs>
                    <rect width="400" height="300" fill="url(#g-card2)"/>
                    <rect width="400" height="300" fill="url(#rg2)"/>
                    <!-- Chat bubbles -->
                    <rect x="15" y="20" width="200" height="50" rx="12" fill="rgba(139,92,246,0.2)" stroke="rgba(139,92,246,0.3)" stroke-width="1"/>
                    <text x="30" y="42" fill="rgba(255,255,255,0.8)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_msg1_line1') }}</text>
                    <text x="30" y="57" fill="rgba(255,255,255,0.8)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_msg1_line2') }}</text>
                    <circle cx="230" cy="45" r="16" fill="linear-gradient(135deg,#8b5cf6,#06b6d4)"/>
                    <rect x="16" y="17" width="5" height="5" rx="1" fill="rgba(139,92,246,0.6)"/>

                    <rect x="185" y="82" width="180" height="36" rx="12" fill="rgba(6,182,212,0.25)" stroke="rgba(6,182,212,0.3)" stroke-width="1"/>
                    <text x="200" y="103" fill="rgba(255,255,255,0.8)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_msg2') }}</text>

                    <rect x="15" y="130" width="310" height="100" rx="12" fill="rgba(139,92,246,0.15)" stroke="rgba(139,92,246,0.25)" stroke-width="1"/>
                    <text x="30" y="150" fill="#c4b5fd" font-size="10" font-weight="700" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_summary_title') }}</text>
                    <circle cx="30" cy="168" r="4" fill="#34d399"/>
                    <text x="42" y="172" fill="rgba(255,255,255,0.7)" font-size="9" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_done') }}</text>
                    <circle cx="30" cy="186" r="4" fill="#fbbf24"/>
                    <text x="42" y="190" fill="rgba(255,255,255,0.7)" font-size="9" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_wip') }}</text>
                    <circle cx="30" cy="204" r="4" fill="#f87171"/>
                    <text x="42" y="208" fill="rgba(255,255,255,0.7)" font-size="9" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_warn') }}</text>
                    <text x="30" y="222" fill="#a78bfa" font-size="9" font-weight="700" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_overall') }}</text>

                    <!-- Input bar -->
                    <rect x="15" y="246" width="370" height="38" rx="10" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
                    <text x="30" y="269" fill="rgba(255,255,255,0.3)" font-size="10" font-family="Inter,sans-serif">{{ __('welcome.svg_chat_placeholder') }}</text>
                    <rect x="350" y="252" width="28" height="26" rx="7" fill="rgba(139,92,246,0.6)"/>
                    <text x="364" y="269" fill="#fff" font-size="12" text-anchor="middle" font-family="Inter,sans-serif">↑</text>
                </svg>
                <div class="ai-img-label">
                    <h4>{{ __('welcome.card2_title') }}</h4>
                    <p>{{ __('welcome.card2_desc') }}</p>
                </div>
            </div>
            <!-- Card 3: Project Board -->
            <div class="ai-img-card reveal" style="transition-delay:.15s;">
                <svg class="ai-img-svg" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g-card3" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0a1e10"/>
                            <stop offset="100%" stop-color="#0f2820"/>
                        </linearGradient>
                        <radialGradient id="rg3" cx="20%" cy="80%" r="60%">
                            <stop offset="0%" stop-color="rgba(52,211,153,0.2)"/>
                            <stop offset="100%" stop-color="transparent"/>
                        </radialGradient>
                    </defs>
                    <rect width="400" height="300" fill="url(#g-card3)"/>
                    <rect width="400" height="300" fill="url(#rg3)"/>
                    <!-- Kanban columns -->
                    <rect x="10" y="10" width="115" height="270" rx="10" fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
                    <text x="67" y="32" fill="rgba(255,255,255,0.5)" font-size="10" text-anchor="middle" font-weight="700" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_todo') }}</text>
                    <rect x="18" y="42" width="99" height="50" rx="8" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
                    <text x="28" y="60" fill="rgba(255,255,255,0.7)" font-size="9" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_login') }}</text>
                    <text x="28" y="74" fill="rgba(255,255,255,0.35)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_priority_high') }}</text>
                    <rect x="78" y="78" width="30" height="10" rx="3" fill="rgba(239,68,68,0.3)"/>
                    <text x="93" y="87" fill="#f87171" font-size="7" text-anchor="middle" font-family="Inter,sans-serif">HIGH</text>
                    <rect x="18" y="100" width="99" height="50" rx="8" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
                    <text x="28" y="118" fill="rgba(255,255,255,0.7)" font-size="9" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_api_doc') }}</text>
                    <text x="28" y="132" fill="rgba(255,255,255,0.35)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_deadline') }}</text>

                    <rect x="135" y="10" width="115" height="270" rx="10" fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
                    <text x="192" y="32" fill="rgba(255,255,255,0.5)" font-size="10" text-anchor="middle" font-weight="700" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_inprogress') }}</text>
                    <rect x="143" y="42" width="99" height="60" rx="8" fill="rgba(6,182,212,0.1)" stroke="rgba(6,182,212,0.25)" stroke-width="1"/>
                    <text x="153" y="60" fill="rgba(255,255,255,0.8)" font-size="9" font-weight="600" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_api') }}</text>
                    <rect x="153" y="66" width="79" height="5" rx="2" fill="rgba(255,255,255,0.1)"/>
                    <rect x="153" y="66" width="67" height="5" rx="2" fill="#67e8f9"/>
                    <text x="153" y="82" fill="rgba(255,255,255,0.35)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_progress') }}</text>
                    <rect x="143" y="112" width="99" height="50" rx="8" fill="rgba(6,182,212,0.1)" stroke="rgba(6,182,212,0.2)" stroke-width="1"/>
                    <text x="153" y="130" fill="rgba(255,255,255,0.8)" font-size="9" font-weight="600" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_tests') }}</text>
                    <text x="153" y="147" fill="rgba(255,255,255,0.35)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_owner') }}</text>

                    <rect x="260" y="10" width="130" height="270" rx="10" fill="rgba(255,255,255,0.03)" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
                    <text x="325" y="32" fill="rgba(255,255,255,0.5)" font-size="10" text-anchor="middle" font-weight="700" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_done') }}</text>
                    <rect x="268" y="42" width="114" height="50" rx="8" fill="rgba(52,211,153,0.1)" stroke="rgba(52,211,153,0.25)" stroke-width="1"/>
                    <text x="278" y="60" fill="rgba(255,255,255,0.8)" font-size="9" font-weight="600" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_ui') }}</text>
                    <text x="278" y="75" fill="#34d399" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_ui_early') }}</text>
                    <rect x="268" y="100" width="114" height="50" rx="8" fill="rgba(52,211,153,0.1)" stroke="rgba(52,211,153,0.2)" stroke-width="1"/>
                    <text x="278" y="118" fill="rgba(255,255,255,0.8)" font-size="9" font-weight="600" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_db') }}</text>
                    <text x="278" y="133" fill="#34d399" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_db_done') }}</text>
                    <rect x="268" y="158" width="114" height="50" rx="8" fill="rgba(52,211,153,0.1)" stroke="rgba(52,211,153,0.2)" stroke-width="1"/>
                    <text x="278" y="176" fill="rgba(255,255,255,0.8)" font-size="9" font-weight="600" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_auth') }}</text>
                    <text x="278" y="191" fill="#34d399" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_kb_auth_done') }}</text>
                </svg>
                <div class="ai-img-label">
                    <h4>{{ __('welcome.card3_title') }}</h4>
                    <p>{{ __('welcome.card3_desc') }}</p>
                </div>
            </div>
            <!-- Card 4: Analytics -->
            <div class="ai-img-card reveal" style="transition-delay:.2s;">
                <svg class="ai-img-svg" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g-card4" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#1e0a38"/>
                            <stop offset="100%" stop-color="#0f0a1e"/>
                        </linearGradient>
                        <radialGradient id="rg4" cx="70%" cy="30%" r="50%">
                            <stop offset="0%" stop-color="rgba(139,92,246,0.3)"/>
                            <stop offset="100%" stop-color="transparent"/>
                        </radialGradient>
                    </defs>
                    <rect width="400" height="300" fill="url(#g-card4)"/>
                    <rect width="400" height="300" fill="url(#rg4)"/>
                    <!-- Line chart -->
                    <text x="20" y="30" fill="rgba(255,255,255,0.6)" font-size="11" font-weight="700" font-family="Inter,sans-serif">{{ __('welcome.svg_analytics_title') }}</text>
                    <!-- Grid lines -->
                    <line x1="20" y1="50" x2="380" y2="50" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="20" y1="80" x2="380" y2="80" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="20" y1="110" x2="380" y2="110" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="20" y1="140" x2="380" y2="140" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="20" y1="170" x2="380" y2="170" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <!-- Line 1 -->
                    <polyline points="20,170 80,150 140,130 200,110 260,85 320,65 380,45" fill="none" stroke="#8b5cf6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="20,170 80,150 140,130 200,110 260,85 320,65 380,45" fill="url(#fill1)" stroke="none" opacity="0.15"/>
                    <!-- Line 2 -->
                    <polyline points="20,160 80,158 140,150 200,135 260,120 320,100 380,80" fill="none" stroke="#67e8f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="6,3"/>
                    <!-- Points -->
                    <circle cx="20" cy="170" r="4" fill="#8b5cf6"/>
                    <circle cx="80" cy="150" r="4" fill="#8b5cf6"/>
                    <circle cx="140" cy="130" r="4" fill="#8b5cf6"/>
                    <circle cx="200" cy="110" r="4" fill="#8b5cf6"/>
                    <circle cx="260" cy="85" r="4" fill="#8b5cf6"/>
                    <circle cx="320" cy="65" r="4" fill="#8b5cf6"/>
                    <circle cx="380" cy="45" r="4" fill="#34d399"/>
                    <!-- Labels -->
                    <text x="20" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month1') }}</text>
                    <text x="78" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month2') }}</text>
                    <text x="138" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month3') }}</text>
                    <text x="198" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month4') }}</text>
                    <text x="258" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month5') }}</text>
                    <text x="318" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month6') }}</text>
                    <text x="375" y="185" fill="rgba(255,255,255,0.3)" font-size="8" font-family="Inter,sans-serif">{{ __('welcome.svg_month7') }}</text>
                    <!-- Stat boxes -->
                    <rect x="20" y="200" width="110" height="80" rx="12" fill="rgba(139,92,246,0.1)" stroke="rgba(139,92,246,0.25)" stroke-width="1"/>
                    <text x="75" y="232" fill="#c4b5fd" font-size="22" font-weight="900" text-anchor="middle" font-family="Inter,sans-serif">+73%</text>
                    <text x="75" y="248" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="middle" font-family="Inter,sans-serif">{{ __('welcome.svg_stat_prod') }}</text>
                    <rect x="145" y="200" width="110" height="80" rx="12" fill="rgba(6,182,212,0.1)" stroke="rgba(6,182,212,0.25)" stroke-width="1"/>
                    <text x="200" y="232" fill="#67e8f9" font-size="22" font-weight="900" text-anchor="middle" font-family="Inter,sans-serif">-30%</text>
                    <text x="200" y="248" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="middle" font-family="Inter,sans-serif">{{ __('welcome.svg_stat_meeting') }}</text>
                    <rect x="270" y="200" width="110" height="80" rx="12" fill="rgba(52,211,153,0.1)" stroke="rgba(52,211,153,0.25)" stroke-width="1"/>
                    <text x="325" y="232" fill="#34d399" font-size="22" font-weight="900" text-anchor="middle" font-family="Inter,sans-serif">4.9★</text>
                    <text x="325" y="248" fill="rgba(255,255,255,0.4)" font-size="9" text-anchor="middle" font-family="Inter,sans-serif">{{ __('welcome.svg_stat_satisfaction') }}</text>
                </svg>
                <div class="ai-img-label">
                    <h4>{{ __('welcome.card4_title') }}</h4>
                    <p>{{ __('welcome.card4_desc') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="section features-bg" id="features">
    <div class="s-inner">
        <div class="text-c reveal">
            <div class="s-label">{{ __('welcome.feat_section_label') }}</div>
            <h2 class="s-title" style="color:#f0eeff;">{!! __('welcome.feat_section_title_html') !!}</h2>
            <p class="s-desc">{{ __('welcome.feat_section_desc') }}</p>
        </div>
        <div class="features-grid">
            <div class="feat-card reveal" style="transition-delay:.05s;">
                <div class="feat-icon-wrap" style="background:linear-gradient(135deg,rgba(139,92,246,.3),rgba(109,40,217,.2));">📋</div>
                <h3>{{ __('welcome.feat_pm_title') }}</h3>
                <p>{{ __('welcome.feat_pm_desc') }}</p>
                <div class="feat-tag">{{ __('welcome.feat_pm_tag') }}</div>
            </div>
            <div class="feat-card reveal" style="transition-delay:.1s;">
                <div class="feat-icon-wrap" style="background:linear-gradient(135deg,rgba(6,182,212,.3),rgba(8,145,178,.2));">💬</div>
                <h3>{{ __('welcome.feat_chat_title') }}</h3>
                <p>{{ __('welcome.feat_chat_desc') }}</p>
                <div class="feat-tag">{{ __('welcome.feat_chat_tag') }}</div>
            </div>
            <div class="feat-card reveal" style="transition-delay:.15s;">
                <div class="feat-icon-wrap" style="background:linear-gradient(135deg,rgba(52,211,153,.3),rgba(5,150,105,.2));">📅</div>
                <h3>{{ __('welcome.feat_cal_title') }}</h3>
                <p>{{ __('welcome.feat_cal_desc') }}</p>
                <div class="feat-tag">{{ __('welcome.feat_cal_tag') }}</div>
            </div>
            <div class="feat-card reveal" style="transition-delay:.2s;">
                <div class="feat-icon-wrap" style="background:linear-gradient(135deg,rgba(251,191,36,.3),rgba(245,158,11,.2));">🤖</div>
                <h3>{{ __('welcome.feat_ai_title') }}</h3>
                <p>{{ __('welcome.feat_ai_desc') }}</p>
                <div class="feat-tag">{{ __('welcome.feat_ai_tag') }}</div>
            </div>
            <div class="feat-card reveal" style="transition-delay:.25s;">
                <div class="feat-icon-wrap" style="background:linear-gradient(135deg,rgba(244,114,182,.3),rgba(219,39,119,.2));">📁</div>
                <h3>{{ __('welcome.feat_files_title') }}</h3>
                <p>{{ __('welcome.feat_files_desc') }}</p>
                <div class="feat-tag">{{ __('welcome.feat_files_tag') }}</div>
            </div>
            <div class="feat-card reveal" style="transition-delay:.3s;">
                <div class="feat-icon-wrap" style="background:linear-gradient(135deg,rgba(139,92,246,.3),rgba(167,139,250,.2));">❓</div>
                <h3>{{ __('welcome.feat_qa_title') }}</h3>
                <p>{{ __('welcome.feat_qa_desc') }}</p>
                <div class="feat-tag">{{ __('welcome.feat_qa_tag') }}</div>
            </div>
        </div>
    </div>
</section>

<!-- 웍스 SHOWCASE -->
<section class="section ai-showcase" id="ai">
    <div class="ai-showcase-inner s-inner">
        <div class="ai-showcase-text reveal">
            <div class="s-label">{{ __('welcome.ai_section_label') }}</div>
            <h2 class="s-title">{!! __('welcome.ai_section_title_html') !!}</h2>
            <p class="s-desc" style="color:rgba(255,255,255,.5);">{{ __('welcome.ai_section_desc') }}</p>
            <ul class="ai-features-list">
                <li>
                    <div class="ai-feat-icon">📝</div>
                    <div><span class="ai-feat-strong">{{ __('welcome.ai_feat1_strong') }}</span>{{ __('welcome.ai_feat1_rest') }}</div>
                </li>
                <li>
                    <div class="ai-feat-icon">📊</div>
                    <div><span class="ai-feat-strong">{{ __('welcome.ai_feat2_strong') }}</span>{{ __('welcome.ai_feat2_rest') }}</div>
                </li>
                <li>
                    <div class="ai-feat-icon">🎯</div>
                    <div><span class="ai-feat-strong">{{ __('welcome.ai_feat3_strong') }}</span>{{ __('welcome.ai_feat3_rest') }}</div>
                </li>
                <li>
                    <div class="ai-feat-icon">💡</div>
                    <div><span class="ai-feat-strong">{{ __('welcome.ai_feat4_strong') }}</span>{{ __('welcome.ai_feat4_rest') }}</div>
                </li>
            </ul>
            <a href="{{ route('register') }}" class="btn-glow" style="display:inline-flex;margin-top:1rem;">{{ __('welcome.ai_cta_btn') }}</a>
        </div>
        <div class="ai-visual-panel reveal" style="transition-delay:.2s;">
            <div class="ai-screen">
                <div class="ai-screen-bar">
                    <div class="scr-dot" style="background:#f87171"></div>
                    <div class="scr-dot" style="background:#fbbf24"></div>
                    <div class="scr-dot" style="background:#34d399"></div>
                    <span style="margin-left:.5rem;font-size:.75rem;color:rgba(255,255,255,.35);">{{ __('welcome.ai_widget_title') }}</span>
                    <span style="margin-left:auto;font-size:.7rem;color:#34d399;">{{ __('welcome.ai_widget_status') }}</span>
                </div>
                <div class="ai-screen-body">
                    <div class="ai-msg">
                        <div class="ai-avatar bot">🤖</div>
                        <div class="ai-bubble bot">{{ __('welcome.ai_chat_bot1') }}</div>
                    </div>
                    <div class="ai-msg user">
                        <div class="ai-avatar usr">👤</div>
                        <div class="ai-bubble usr">{{ __('welcome.ai_chat_user1') }}</div>
                    </div>
                    <div class="ai-msg">
                        <div class="ai-avatar bot">🤖</div>
                        <div class="ai-bubble bot">
                            📊 <strong>{{ __('welcome.ai_chat_bot2_head') }}</strong><br><br>
                            {{ __('welcome.ai_chat_bot2_done') }}<br>
                            {{ __('welcome.ai_chat_bot2_wip') }}<br>
                            {{ __('welcome.ai_chat_bot2_warn') }}<br><br>
                            {!! __('welcome.ai_chat_bot2_rate', ['rate' => '<strong style="color:#c4b5fd;">73</strong>']) !!}
                        </div>
                    </div>
                    <div class="ai-msg user">
                        <div class="ai-avatar usr">👤</div>
                        <div class="ai-bubble usr">{{ __('welcome.ai_chat_user2') }}</div>
                    </div>
                    <div class="ai-msg">
                        <div class="ai-avatar bot">🤖</div>
                        <div class="ai-bubble bot" style="display:flex;align-items:center;gap:5px;">
                            <div class="ai-typing">
                                <div class="t-dot"></div>
                                <div class="t-dot"></div>
                                <div class="t-dot"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WORKFLOW -->
<section class="section workflow-bg" id="workflow">
    <div class="s-inner">
        <div class="text-c reveal">
            <div class="s-label">{{ __('welcome.wf_section_label') }}</div>
            <h2 class="s-title" style="color:#f0eeff;">{!! __('welcome.wf_section_title_html') !!}</h2>
            <p class="s-desc">{{ __('welcome.wf_section_desc') }}</p>
        </div>
        <div class="workflow-timeline">
            <div class="wf-step reveal" style="transition-delay:.05s;">
                <div class="wf-icon">👥</div>
                <div class="wf-num">
                    <div class="wf-num-inner">1</div>
                </div>
                <h3>{{ __('welcome.wf_step1_title') }}</h3>
                <p>{{ __('welcome.wf_step1_desc') }}</p>
            </div>
            <div class="wf-step reveal" style="transition-delay:.1s;">
                <div class="wf-icon">🚀</div>
                <div class="wf-num">
                    <div class="wf-num-inner">2</div>
                </div>
                <h3>{{ __('welcome.wf_step2_title') }}</h3>
                <p>{{ __('welcome.wf_step2_desc') }}</p>
            </div>
            <div class="wf-step reveal" style="transition-delay:.15s;">
                <div class="wf-icon">📋</div>
                <div class="wf-num">
                    <div class="wf-num-inner">3</div>
                </div>
                <h3>{{ __('welcome.wf_step3_title') }}</h3>
                <p>{{ __('welcome.wf_step3_desc') }}</p>
            </div>
            <div class="wf-step reveal" style="transition-delay:.2s;">
                <div class="wf-icon">⚡</div>
                <div class="wf-num">
                    <div class="wf-num-inner">4</div>
                </div>
                <h3>{{ __('welcome.wf_step4_title') }}</h3>
                <p>{{ __('welcome.wf_step4_desc') }}</p>
            </div>
            <div class="wf-step reveal" style="transition-delay:.25s;">
                <div class="wf-icon">📈</div>
                <div class="wf-num">
                    <div class="wf-num-inner">5</div>
                </div>
                <h3>{{ __('welcome.wf_step5_title') }}</h3>
                <p>{{ __('welcome.wf_step5_desc') }}</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="section testi-bg" id="testimonials">
    <div class="s-inner">
        <div class="text-c reveal">
            <div class="s-label">{{ __('welcome.testi_section_label') }}</div>
            <h2 class="s-title" style="color:#f0eeff;">{{ __('welcome.testi_section_title') }}</h2>
            <p class="s-desc">{{ __('welcome.testi_section_desc') }}</p>
        </div>
        <div class="testi-grid">
            <div class="testi-card reveal" style="transition-delay:.05s;">
                <div class="testi-stars">★★★★★</div>
                <blockquote>{{ __('welcome.testi1_quote') }}</blockquote>
                <div class="testi-author">
                    <div class="testi-avatar" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">{{ __('welcome.testi1_avatar') }}</div>
                    <div>
                        <div class="testi-name">{{ __('welcome.testi1_name') }}</div>
                        <div class="testi-role">{{ __('welcome.testi1_role') }}</div>
                    </div>
                </div>
            </div>
            <div class="testi-card reveal" style="transition-delay:.1s;">
                <div class="testi-stars">★★★★★</div>
                <blockquote>{{ __('welcome.testi2_quote') }}</blockquote>
                <div class="testi-author">
                    <div class="testi-avatar" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">{{ __('welcome.testi2_avatar') }}</div>
                    <div>
                        <div class="testi-name">{{ __('welcome.testi2_name') }}</div>
                        <div class="testi-role">{{ __('welcome.testi2_role') }}</div>
                    </div>
                </div>
            </div>
            <div class="testi-card reveal" style="transition-delay:.15s;">
                <div class="testi-stars">★★★★★</div>
                <blockquote>{{ __('welcome.testi3_quote') }}</blockquote>
                <div class="testi-author">
                    <div class="testi-avatar" style="background:linear-gradient(135deg,#10b981,#059669);">{{ __('welcome.testi3_avatar') }}</div>
                    <div>
                        <div class="testi-name">{{ __('welcome.testi3_name') }}</div>
                        <div class="testi-role">{{ __('welcome.testi3_role') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="reveal" style="position:relative;z-index:1;">
        <h2>{{ __('welcome.cta_title') }}</h2>
        <p>{{ __('welcome.cta_desc') }}</p>
        <a href="{{ route('register') }}" class="btn-cta-main">{{ __('welcome.cta_btn') }}</a>
        <div class="cta-note">{{ __('welcome.cta_note') }}</div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-main">

        {{-- Brand / Company --}}
        <div class="footer-col">
            <div class="footer-brand-name">⚡ SupportWorks</div>
            <p class="footer-brand-desc">{!! __('welcome.footer_brand_desc_html') !!}</p>
            <div class="footer-biz">
                <div><strong style="color:rgba(255,255,255,.35);">{{ __('welcome.footer_biz_corp') }}</strong> &nbsp;{{ __('welcome.footer_biz_corp_val') }}</div>
                <div><strong style="color:rgba(255,255,255,.35);">{{ __('welcome.footer_biz_ceo') }}</strong> &nbsp;{{ __('welcome.footer_biz_ceo_val') }}</div>
                <div><strong style="color:rgba(255,255,255,.35);">{{ __('welcome.footer_biz_reg') }}</strong> &nbsp;{{ __('welcome.footer_biz_reg_val') }}</div>
                <div><strong style="color:rgba(255,255,255,.35);">{{ __('welcome.footer_biz_ecom') }}</strong> &nbsp;{{ __('welcome.footer_biz_ecom_val') }}</div>
                <div><strong style="color:rgba(255,255,255,.35);">{!! __('welcome.footer_biz_addr') !!}</strong> &nbsp;{{ __('welcome.footer_biz_addr_val') }}</div>
            </div>
        </div>

        {{-- Service --}}
        <div class="footer-col">
            <div class="footer-col-title">{{ __('welcome.footer_col_service') }}</div>
            <ul>
                <li><a href="#">{{ __('welcome.footer_link_about') }}</a></li>
                <li><a href="#">{{ __('welcome.footer_link_features') }}</a></li>
                <li><a href="#">{{ __('welcome.footer_link_pricing') }}</a></li>
                <li><a href="#">{{ __('welcome.footer_link_updates') }}</a></li>
                <li><a href="#">{{ __('welcome.footer_link_cases') }}</a></li>
            </ul>
        </div>

        {{-- Legal --}}
        <div class="footer-col">
            <div class="footer-col-title">{{ __('welcome.footer_col_policy') }}</div>
            <ul>
                <li><a href="{{ route('policy.terms') }}">{{ __('welcome.footer_link_terms') }}</a></li>
                <li><a href="{{ route('policy.privacy') }}">{{ __('welcome.footer_link_privacy') }}</a></li>
                <li><a href="{{ route('policy.cookie') }}">{{ __('welcome.footer_link_cookie') }}</a></li>
                <li><a href="{{ route('policy.youth') }}">{{ __('welcome.footer_link_youth') }}</a></li>
            </ul>
        </div>

        {{-- Support --}}
        <div class="footer-col">
            <div class="footer-col-title">{{ __('welcome.footer_col_support') }}</div>
            <div class="footer-contact-item">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <p><a href="mailto:adm@linkthelab.co.kr">adm@linkthelab.co.kr</a></p>
            </div>
            <div class="footer-contact-item">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <p>02-1544-9086<br><span style="font-size:.7rem;opacity:.6;">{{ __('welcome.footer_phone_hours') }}</span></p>
            </div>
            <div class="footer-contact-item">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 11.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <p><a href="{{ route('inquiry.index') }}">{{ __('welcome.footer_inquiry_link') }}</a></p>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <div class="footer-copy">{{ __('welcome.footer_copy') }}</div>
        <div class="footer-bottom-links">
            <a href="{{ route('policy.terms') }}">{{ __('welcome.footer_link_terms') }}</a>
            <a href="{{ route('policy.privacy') }}">{{ __('welcome.footer_link_privacy') }}</a>
            <a href="{{ route('policy.cookie') }}">{{ __('welcome.footer_link_cookie') }}</a>
        </div>
    </div>
</footer>

<script>
// Inject translated strings for use in JS
const WELCOME_STR = {
    stat10x:   '{{ __("welcome.stat_productivity_num") }}',
    stat5min:  '{{ __("welcome.stat_setup_num") }}',
    xSuffix:   '{{ __("welcome.js_counter_x_suffix") }}',
    minSuffix: '{{ __("welcome.js_counter_min_suffix") }}',
    js10x:     '{{ __("welcome.js_stat_10x") }}',
    js5min:    '{{ __("welcome.js_stat_5min") }}',
};

// ── Particle canvas background ──
const canvas = document.getElementById('hero-canvas');
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
        this.vx = (Math.random() - 0.5) * 0.4;
        this.vy = (Math.random() - 0.5) * 0.4;
        this.r = Math.random() * 1.5 + 0.5;
        this.alpha = Math.random() * 0.5 + 0.1;
        const colors = ['#8b5cf6','#06b6d4','#34d399','#a78bfa','#67e8f9'];
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

for (let i = 0; i < 120; i++) particles.push(new Particle());

function drawConnections() {
    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 90) {
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = '#8b5cf6';
                ctx.globalAlpha = (1 - dist / 90) * 0.12;
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

// ── Mouse parallax on feature cards ──
document.querySelectorAll('.feat-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        card.style.setProperty('--mx', x + '%');
        card.style.setProperty('--my', y + '%');
    });
});

// ── Scroll reveal ──
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Nav shadow on scroll ──
const nav = document.querySelector('.nav');
window.addEventListener('scroll', () => {
    nav.style.background = window.scrollY > 40
        ? 'rgba(15,10,30,0.92)' : 'rgba(15,10,30,0.6)';
});

// ── Counter animation for hero stats ──
function animateCounter(el, target, suffix) {
    let start = 0;
    const duration = 1800;
    const step = timestamp => {
        if (!start) start = timestamp;
        const progress = Math.min((timestamp - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        if (typeof target === 'number') {
            el.textContent = Math.floor(eased * target) + suffix;
        }
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

const statsObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const nums = entry.target.querySelectorAll('.hero-stat-num');
            nums.forEach(n => {
                const text = n.textContent;
                if (text.includes(WELCOME_STR.js10x)) animateCounter(n, 10, WELCOME_STR.xSuffix);
                if (text.includes(WELCOME_STR.js5min)) animateCounter(n, 5, WELCOME_STR.minSuffix);
                if (text.includes('24/7')) { /* static */ }
            });
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });
const statsEl = document.querySelector('.hero-stats');
if (statsEl) statsObserver.observe(statsEl);
</script>

</body>
</html>
