<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('errors.404_title') }} — SupportWorks</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Apple SD Gothic Neo','Noto Sans KR','맑은 고딕',sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.wrap{max-width:520px;width:100%;padding:24px;}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:48px 40px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.06);}
.icon-wrap{width:72px;height:72px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;}
.icon-wrap svg{width:34px;height:34px;stroke:#fff;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.code{font-size:13px;font-weight:700;color:#6366f1;letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px;}
h1{font-size:22px;font-weight:800;color:#1e293b;margin-bottom:12px;line-height:1.3;}
.desc{font-size:14px;color:#64748b;line-height:1.7;margin-bottom:28px;}
.btn-home{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;background:#6366f1;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;text-decoration:none;transition:background .15s;}
.btn-home:hover{background:#4f46e5;}
.btn-home svg{width:16px;height:16px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.footer{margin-top:20px;font-size:11px;color:#94a3b8;}
.big-num{font-size:80px;font-weight:900;color:#e2e8f0;line-height:1;margin-bottom:8px;letter-spacing:-4px;}
</style>
</head>
<body>
<div class="wrap">
    <div style="text-align:center;margin-bottom:20px;">
        <div style="display:inline-flex;align-items:center;gap:8px;">
            <div style="width:30px;height:30px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;color:#fff;">S</div>
            <span style="font-size:15px;font-weight:700;color:#1e293b;">SupportWorks</span>
        </div>
    </div>
    <div class="card">
        <p class="big-num">404</p>
        <h1>{!! __('errors.404_heading') !!}</h1>
        <p class="desc">{!! __('errors.404_desc') !!}</p>
        <a href="{{ url('/') }}" class="btn-home">
            <svg viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            {{ __('errors.home') }}
        </a>
    </div>
    <p class="footer" style="text-align:center;">© {{ date('Y') }} SupportWorks. All rights reserved.</p>
</div>
</body>
</html>
