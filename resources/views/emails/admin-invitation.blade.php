<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('emails.invite_admin_title') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">

      {{-- 헤더 --}}
      <tr>
        <td style="background:linear-gradient(135deg,#6d4aff,#5b3fe0);padding:32px 40px;text-align:center;">
          <p style="margin:0;font-size:22px;font-weight:700;color:#fff;letter-spacing:-.3px;">SupportWorks</p>
          <p style="margin:8px 0 0;font-size:13px;color:rgba(255,255,255,.75);">{{ __('emails.invite_admin_subtitle') }}</p>
        </td>
      </tr>

      {{-- 본문 --}}
      <tr>
        <td style="padding:36px 40px 28px;">
          <p style="margin:0 0 8px;font-size:15px;font-weight:600;color:#18181b;">{{ __('emails.invite_admin_greeting', ['name' => $invitation->name]) }}</p>
          <p style="margin:0 0 24px;font-size:14px;color:#52525b;line-height:1.7;">
            <strong style="color:#18181b;">{{ $inviterName }}</strong>{{ __('emails.invite_admin_body', ['inviter' => '']) }}<br>
            {{ __('emails.invite_admin_body2') }}
          </p>

          {{-- 역할 배지 --}}
          <div style="background:#f4f4f5;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="font-size:12px;color:#71717a;padding-bottom:4px;">{{ __('emails.invite_admin_info_label') }}</td>
              </tr>
              <tr>
                <td style="font-size:13px;color:#3f3f46;font-weight:500;">
                  {{ __('emails.invite_admin_role_label') }} <span style="background:#ede9fe;color:#5b3fe0;padding:2px 10px;border-radius:20px;font-size:12px;">{{ $roleName }}</span>
                </td>
              </tr>
              <tr>
                <td style="font-size:12px;color:#a1a1aa;padding-top:6px;">
                  {{ __('emails.invite_admin_expires', ['date' => $invitation->expires_at->format('Y년 m월 d일 H:i')]) }}
                </td>
              </tr>
            </table>
          </div>

          {{-- CTA 버튼 --}}
          <div style="text-align:center;margin-bottom:28px;">
            <a href="{{ $acceptUrl }}"
               style="display:inline-block;background:linear-gradient(135deg,#6d4aff,#5b3fe0);color:#fff;text-decoration:none;padding:14px 36px;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-.2px;">
              {{ __('emails.invite_admin_cta') }}
            </a>
          </div>

          {{-- URL 직접 입력 --}}
          <p style="margin:0 0 6px;font-size:12px;color:#a1a1aa;">{{ __('emails.invite_admin_url_hint') }}</p>
          <p style="margin:0 0 24px;font-size:11px;color:#6d4aff;word-break:break-all;">{{ $acceptUrl }}</p>

          {{-- 보안 안내 --}}
          <div style="border-top:1px solid #f4f4f5;padding-top:20px;">
            <p style="margin:0 0 6px;font-size:12px;color:#a1a1aa;font-weight:600;">{{ __('emails.invite_admin_security_title') }}</p>
            <ul style="margin:0;padding-left:16px;">
              <li style="font-size:12px;color:#a1a1aa;margin-bottom:4px;">{{ __('emails.invite_admin_security_1') }}</li>
              <li style="font-size:12px;color:#a1a1aa;margin-bottom:4px;">{{ __('emails.invite_admin_security_2') }}</li>
              <li style="font-size:12px;color:#a1a1aa;">{{ __('emails.invite_admin_security_3') }}</li>
            </ul>
          </div>
        </td>
      </tr>

      {{-- 푸터 --}}
      <tr>
        <td style="background:#fafafa;padding:20px 40px;text-align:center;border-top:1px solid #f4f4f5;">
          <p style="margin:0;font-size:11px;color:#a1a1aa;">
            &copy; {{ date('Y') }} SupportWorks. All rights reserved.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
