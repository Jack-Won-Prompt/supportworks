<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $emailSubject }}</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);padding:28px 36px 22px;">
        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6d28d9;letter-spacing:.06em;text-transform:uppercase;">보낸 사람</p>
        <p style="margin:0;font-size:15px;font-weight:700;color:#4c1d95;">{{ $senderName }}@if($senderEmail) <span style="font-weight:500;font-size:12px;color:#7c3aed;">&lt;{{ $senderEmail }}&gt;</span>@endif</p>
        @if($recipientName)
        <p style="margin:10px 0 0;font-size:12px;color:#6d28d9;">받는 분: {{ $recipientName }}</p>
        @endif
      </td>
    </tr>
    <tr>
      <td style="padding:26px 36px 6px;">
        <h1 style="margin:0;font-size:18px;font-weight:700;color:#18181b;line-height:1.35;">{{ $emailSubject }}</h1>
      </td>
    </tr>
    <tr>
      <td style="padding:18px 36px 28px;">
        <div style="font-size:14px;color:#27272a;line-height:1.7;white-space:pre-wrap;word-break:break-word;">{!! nl2br(e($emailBody)) !!}</div>
      </td>
    </tr>
    <tr>
      <td style="padding:14px 36px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">SupportWorks · {{ $senderName }}님이 보낸 메일</p>
      </td>
    </tr>
  </table>
</td></tr></table>
</body>
</html>
