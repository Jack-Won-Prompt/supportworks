<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('emails.reset_title') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Apple SD Gothic Neo','Malgun Gothic',sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:48px 16px;">
<tr><td align="center">

  {{-- 카드 --}}
  <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.06),0 0 0 1px rgba(0,0,0,.04);">

    {{-- 상단 보라 스트라이프 --}}
    <tr>
      <td style="background:linear-gradient(90deg,#8b5cf6,#6d28d9);height:4px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    {{-- 헤더 --}}
    <tr>
      <td style="padding:36px 44px 28px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="vertical-align:top;">
              <div style="display:inline-block;width:44px;height:44px;border-radius:12px;background:#eef2ff;border:1.5px solid #e0e7ff;text-align:center;line-height:44px;font-size:20px;">🔑</div>
            </td>
            <td style="padding-left:16px;vertical-align:middle;">
              <p style="margin:0 0 4px;font-size:18px;font-weight:700;color:#111827;letter-spacing:-.3px;">{{ __('emails.reset_header') }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">
                SupportWorks
                &nbsp;·&nbsp;
                <span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;background:#ede9fe;color:#5b21b6;">{{ __('emails.reset_badge') }}</span>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 구분선 --}}
    <tr><td style="padding:0 44px;"><div style="height:1px;background:#f3f4f6;"></div></td></tr>

    {{-- 인사 & 안내 문구 --}}
    <tr>
      <td style="padding:28px 44px 0;">
        <p style="margin:0 0 10px;font-size:15px;font-weight:600;color:#111827;">{{ __('emails.reset_greeting', ['name' => $user->name]) }} 👋</p>
        <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.75;">
          {{ __('emails.reset_body1') }}<br>
          {{ __('emails.reset_body2') }}<br>
          {{ __('emails.reset_body3') }}
        </p>
      </td>
    </tr>

    {{-- 계정 정보 카드 --}}
    <tr>
      <td style="padding:20px 44px 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:12px;border:1px solid #f3f4f6;overflow:hidden;">
          <tr>
            <td style="padding:13px 20px;border-bottom:1px solid #f3f4f6;">
              <p style="margin:0;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;">{{ __('emails.reset_account_info') }}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 20px;border-bottom:1px solid #f3f4f6;">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="width:64px;font-size:12px;color:#9ca3af;vertical-align:middle;">{{ __('emails.reset_name_label') }}</td>
                <td style="font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $user->name }}</td>
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 20px;">
              <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="width:64px;font-size:12px;color:#9ca3af;vertical-align:middle;">{{ __('emails.reset_email_label') }}</td>
                <td style="font-size:14px;font-weight:600;color:#111827;vertical-align:middle;">{{ $user->email }}</td>
              </tr></table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 안내 배너 --}}
    <tr>
      <td style="padding:16px 44px 0;">
        <div style="padding:14px 18px;background:#eef2ff;border-left:3px solid #6d28d9;border-radius:0 8px 8px 0;">
          <p style="margin:0;font-size:13px;color:#3730a3;line-height:1.6;">
            ⏱ {{ __('emails.reset_expiry_note') }}
          </p>
        </div>
      </td>
    </tr>

    {{-- CTA 버튼 --}}
    <tr>
      <td style="padding:28px 44px 36px;text-align:center;">
        <a href="{{ $resetUrl }}"
           style="display:inline-block;padding:14px 42px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#ffffff;font-size:14px;font-weight:700;border-radius:12px;text-decoration:none;letter-spacing:.01em;box-shadow:0 4px 14px rgba(109,40,217,.35);">
          {{ __('emails.reset_cta') }}
        </a>
        <p style="margin:16px 0 0;font-size:12px;color:#9ca3af;">
          {{ __('emails.reset_url_hint') }}
        </p>
        <p style="margin:6px 0 0;font-size:11px;">
          <a href="{{ $resetUrl }}" style="color:#8b5cf6;word-break:break-all;text-decoration:none;">{{ $resetUrl }}</a>
        </p>
      </td>
    </tr>

    {{-- 구분선 --}}
    <tr><td><div style="height:1px;background:#f3f4f6;"></div></td></tr>

    {{-- 보안 경고 --}}
    <tr>
      <td style="padding:18px 44px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="vertical-align:top;padding-top:1px;">
              <span style="font-size:16px;">🛡️</span>
            </td>
            <td style="padding-left:10px;">
              <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.65;">
                <strong style="color:#6b7280;">{{ __('emails.reset_security_title') }}</strong> — {{ __('emails.reset_security_body') }}
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    {{-- 구분선 --}}
    <tr><td><div style="height:1px;background:#f3f4f6;"></div></td></tr>

    {{-- 푸터 --}}
    <tr>
      <td style="padding:20px 44px;text-align:center;">
        <p style="margin:0;font-size:11px;color:#d1d5db;line-height:1.7;">
          {{ __('emails.reset_footer_note') }}<br>
          {{ __('emails.reset_footer_platform') }}
        </p>
      </td>
    </tr>

  </table>

  {{-- 하단 여백 텍스트 --}}
  <p style="margin:24px 0 0;font-size:11px;color:#9ca3af;text-align:center;">
    © {{ date('Y') }} SupportWorks. All rights reserved.
  </p>

</td></tr>
</table>
</body>
</html>
