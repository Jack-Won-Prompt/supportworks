<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>{{ $taskTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f0eeff;font-family:'Apple SD Gothic Neo','Malgun Gothic',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0eeff;padding:40px 16px;">
<tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;border:1px solid #ddd6fe;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <tr>
      <td style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);padding:24px 32px 18px;">
        <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#6d28d9;letter-spacing:.06em;text-transform:uppercase;">일정 담당자 지정</p>
        <h1 style="margin:0;font-size:18px;font-weight:700;color:#4c1d95;line-height:1.35;">{{ $recipientName }} 님,<br>다음 일정의 담당자로 지정되었습니다.</h1>
      </td>
    </tr>
    <tr>
      <td style="padding:22px 32px 8px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#27272a;">
          <tr><td style="padding:6px 0;color:#6b7280;width:90px;">프로젝트</td><td style="padding:6px 0;font-weight:600;">{{ $projectName }}</td></tr>
          <tr><td style="padding:6px 0;color:#6b7280;">업무</td><td style="padding:6px 0;font-weight:600;">{{ $taskTitle }}</td></tr>
          <tr><td style="padding:6px 0;color:#6b7280;">기간</td><td style="padding:6px 0;">{{ $startDate ?: '-' }} ~ {{ $endDate ?: '-' }}</td></tr>
          <tr><td style="padding:6px 0;color:#6b7280;">현재 상태</td><td style="padding:6px 0;">{{ $statusLabel }}</td></tr>
          <tr><td style="padding:6px 0;color:#6b7280;">지정자</td><td style="padding:6px 0;">{{ $assignerName }}</td></tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding:14px 32px 24px;">
        <a href="{{ $taskUrl }}" style="display:inline-block;padding:11px 22px;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;">일정 보기 →</a>
      </td>
    </tr>
    <tr>
      <td style="padding:14px 32px;background:#f9fafb;border-top:1px solid #f3f4f6;text-align:center;">
        <p style="margin:0;font-size:11px;color:#9ca3af;">SupportWorks · {{ $assignerName }}님이 일정 담당자로 지정했습니다</p>
      </td>
    </tr>
  </table>
</td></tr></table>
</body>
</html>
