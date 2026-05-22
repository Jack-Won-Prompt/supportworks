<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('errors.503_title') }} — SupportWorks</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Apple SD Gothic Neo','Noto Sans KR','맑은 고딕',sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.wrap{max-width:520px;width:100%;padding:24px;}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:48px 40px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.06);}
.icon-wrap{width:72px;height:72px;background:linear-gradient(135deg,#f59e0b,#f97316);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;}
.icon-wrap svg{width:34px;height:34px;stroke:#fff;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.code{font-size:13px;font-weight:700;color:#f59e0b;letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px;}
h1{font-size:22px;font-weight:800;color:#1e293b;margin-bottom:12px;line-height:1.3;}
.desc{font-size:14px;color:#64748b;line-height:1.7;margin-bottom:28px;}
.notice{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;font-size:13px;color:#92400e;line-height:1.6;margin-bottom:28px;text-align:left;}
.notice strong{display:block;font-weight:700;margin-bottom:2px;}
.footer{margin-top:20px;font-size:11px;color:#94a3b8;}
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
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <p class="code">503 Service Unavailable</p>
        <h1>{{ __('errors.503_heading') }}</h1>
        <p class="desc">{!! __('errors.503_desc') !!}</p>
        <div class="notice">
            <strong>{{ __('errors.admin_notified') }}</strong>
            {{ __('errors.resolving_soon') }}
        </div>
    </div>
    <p class="footer" style="text-align:center;">© {{ date('Y') }} SupportWorks. All rights reserved.</p>
</div>
</body>
</html>
