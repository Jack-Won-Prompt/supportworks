<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.login_title') }} — SupportWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{min-height:100vh;background:linear-gradient(135deg,#0f0c29,#1e1b4b,#302b63);display:flex;align-items:center;justify-content:center;font-family:'Pretendard','Noto Sans KR',sans-serif;}
        .card{background:rgba(255,255,255,.05);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:40px 36px;width:100%;max-width:400px;box-shadow:0 25px 60px rgba(0,0,0,.4);}
        .logo{text-align:center;margin-bottom:28px;}
        .logo-icon{width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;color:#fff;margin:0 auto 12px;}
        .logo h1{font-size:20px;font-weight:800;color:#fff;}
        .logo p{font-size:12px;color:rgba(255,255,255,.4);margin-top:4px;}
        .label{font-size:12px;font-weight:600;color:rgba(255,255,255,.6);display:block;margin-bottom:6px;}
        .input{width:100%;padding:11px 14px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;font-size:14px;outline:none;transition:border .2s;}
        .input::placeholder{color:rgba(255,255,255,.25);}
        .input:focus{border-color:#6366f1;background:rgba(99,102,241,.08);}
        .field{margin-bottom:16px;}
        .btn{width:100%;padding:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;margin-top:6px;transition:opacity .2s;letter-spacing:.02em;}
        .btn:hover{opacity:.88;}
        .error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:10px 13px;font-size:12px;color:#fca5a5;margin-bottom:14px;}
        .remember{display:flex;align-items:center;gap:7px;font-size:12px;color:rgba(255,255,255,.45);margin-top:12px;}
        .remember input{accent-color:#6366f1;}
        .divider{display:flex;align-items:center;gap:10px;margin:18px 0 14px;}
        .divider span{font-size:11px;color:rgba(255,255,255,.25);}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.1);}
        .back-link{display:block;text-align:center;font-size:12px;color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s;}
        .back-link:hover{color:rgba(255,255,255,.7);}
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">A</div>
        <h1>{{ __('admin.login_title') }}</h1>
        <p>SupportWorks Admin Panel</p>
    </div>

    @if($errors->any())
    <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf
        <div class="field">
            <label class="label">{{ __('admin.login_id_label') }}</label>
            <input type="text" name="login_id" class="input" placeholder="{{ __('admin.login_id_placeholder') }}" value="{{ old('login_id') }}" autofocus required>
        </div>
        <div class="field">
            <label class="label">{{ __('admin.password_label') }}</label>
            <input type="password" name="password" class="input" placeholder="{{ __('admin.password_placeholder') }}" required>
        </div>
        <label class="remember">
            <input type="checkbox" name="remember"> {{ __('admin.remember_me') }}
        </label>
        <button type="submit" class="btn">{{ __('admin.login_btn') }}</button>
    </form>

    <div class="divider"><span>{{ __('admin.or_divider') }}</span></div>
    <a href="{{ url('/') }}" class="back-link">{{ __('admin.back_to_user') }}</a>
</div>
</body>
</html>
