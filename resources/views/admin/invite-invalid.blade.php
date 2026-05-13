<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('admin.invite_invalid_title') }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.09); padding: 48px 40px; width: 100%; max-width: 400px; text-align: center; }
.icon { width: 64px; height: 64px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
h2 { font-size: 20px; font-weight: 700; color: #18181b; margin-bottom: 12px; }
p { font-size: 14px; color: #52525b; line-height: 1.7; }
</style>
</head>
<body>
<div class="card">
    <div class="icon">
        <svg width="28" height="28" fill="none" stroke="#ef4444" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </div>
    <h2>{{ __('admin.invite_invalid_heading') }}</h2>
    <p>{{ $reason }}</p>
</div>
</body>
</html>
