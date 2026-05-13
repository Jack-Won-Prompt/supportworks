<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'SupportWorks') }} — 시스템 점검 중</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans KR', sans-serif; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            padding: 20px;
            color: #1e293b;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 20px;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.35);
        }
        .icon-wrap svg { width: 40px; height: 40px; color: #fff; }
        h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 12px;
            color: #1e293b;
            letter-spacing: -0.02em;
        }
        .sub {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .message-box {
            background: #fef9c3;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 16px 18px;
            font-size: 13px;
            color: #78350f;
            line-height: 1.7;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 24px;
        }
        .footer {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }
        .pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 1.4s ease-in-out infinite;
            vertical-align: middle;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h1><span class="pulse"></span>시스템 점검 중</h1>
        <p class="sub">현재 서비스 점검이 진행 중입니다.<br>이용에 불편을 드려 죄송합니다.</p>

        @if(!empty($message))
        <div class="message-box">{{ $message }}</div>
        @endif

        <p class="footer">점검 완료 후 다시 접속해 주세요.</p>
    </div>
</body>
</html>
